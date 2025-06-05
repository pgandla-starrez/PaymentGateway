<?php

declare(strict_types=1);

namespace Tests\Service;

use App\Entity\Order;
use App\Exception\GatewayException;
use App\Exception\ValidationException;
use App\Gateway\ThirdPartyPaymentGateway;
use App\Repository\OrderRepository;
use App\Service\NotificationService;
use App\Service\PaymentProcessor;
use PDOException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class PaymentProcessorTest extends TestCase
{
    private MockObject|OrderRepository $orderRepositoryMock;
    private MockObject|ThirdPartyPaymentGateway $paymentGatewayMock;
    private MockObject|NotificationService $notificationServiceMock;
    private PaymentProcessor $paymentProcessor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->orderRepositoryMock = $this->createMock(OrderRepository::class);
        $this->paymentGatewayMock = $this->createMock(ThirdPartyPaymentGateway::class);
        $this->notificationServiceMock = $this->createMock(NotificationService::class);

        $this->paymentProcessor = new PaymentProcessor(
            $this->orderRepositoryMock,
            $this->paymentGatewayMock,
            $this->notificationServiceMock
        );
    }

    private function getValidPaymentData(array $overrides = []): array
    {
        return array_merge([
            'amount' => 100.00,
            'currency' => 'USD',
            'cardNumber' => '4111111111111111',
            'expiryMonth' => '12',
            'expiryYear' => '2025',
            'cvv' => '123',
            'customerEmail' => 'test@example.com',
        ], $overrides);
    }

    public function testProcessPaymentSuccessful(): void
    {
        $paymentData = $this->getValidPaymentData();
        $expectedOrder = new Order((float)$paymentData['amount'], (string)$paymentData['currency'], (string)$paymentData['cardNumber']);
        $expectedOrder->setId(1);
        $expectedOrder->setStatus('completed');
        $expectedOrder->setGatewayTransactionId('auth_123');

        $this->paymentGatewayMock->expects($this->once())
            ->method('authorizePayment')
            ->with(
                (float)$paymentData['amount'],
                (string)$paymentData['currency'],
                [
                    'number' => (string)$paymentData['cardNumber'],
                    'expiryMonth' => (string)$paymentData['expiryMonth'],
                    'expiryYear' => (string)$paymentData['expiryYear'],
                    'cvv' => (string)$paymentData['cvv'],
                ]
            )
            ->willReturn(['status' => 'authorized', 'transactionId' => 'auth_123']);

        $this->paymentGatewayMock->expects($this->once())
            ->method('capturePayment')
            ->with('auth_123', (float)$paymentData['amount'])
            ->willReturn(['status' => 'captured', 'captureId' => 'cap_456']);

        $this->orderRepositoryMock->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(Order::class))
            ->willReturnCallback(function (Order $order) use ($expectedOrder) {
                $order->setId($expectedOrder->getId()); // Simulate ID assignment on save
                $order->setStatus('completed'); // Simulate status update on save
                $order->setGatewayTransactionId('auth_123');
                return $order;
            });

        $this->notificationServiceMock->expects($this->once())
            ->method('sendPaymentConfirmation')
            ->with($this->isInstanceOf(Order::class), (string)$paymentData['customerEmail'])
            ->willReturn(true);

        $resultOrder = $this->paymentProcessor->processPayment($paymentData);

        $this->assertInstanceOf(Order::class, $resultOrder);
        $this->assertEquals($expectedOrder->getId(), $resultOrder->getId());
        $this->assertEquals('completed', $resultOrder->getStatus());
        $this->assertEquals('auth_123', $resultOrder->getGatewayTransactionId());
        $this->assertEquals((float)$paymentData['amount'], $resultOrder->getAmount());
    }

    public function testProcessPaymentValidationFailureAmount(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Payment data validation failed.');

        $paymentData = $this->getValidPaymentData(['amount' => 0]);
        try {
            $this->paymentProcessor->processPayment($paymentData);
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('amount', $e->getErrors());
            $this->assertEquals('A positive amount is required.', $e->getErrors()['amount']);
            throw $e;
        }
    }
    
    public function testProcessPaymentValidationFailureCurrency(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Payment data validation failed.');

        $paymentData = $this->getValidPaymentData(['currency' => 'US']);
        try {
            $this->paymentProcessor->processPayment($paymentData);
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('currency', $e->getErrors());
            $this->assertEquals('A valid 3-letter currency code is required.', $e->getErrors()['currency']);
            throw $e;
        }
    }

    public function testProcessPaymentGatewayAuthorizationDeclined(): void
    {
        $paymentData = $this->getValidPaymentData();
        $this->expectException(GatewayException::class);
        $this->expectExceptionMessage('Payment declined by issuer.');

        $this->paymentGatewayMock->expects($this->once())
            ->method('authorizePayment')
            ->willThrowException(new GatewayException('Payment declined by issuer.', 402));
        
        // Ensure orderRepository->save is potentially called to update status to failed_gateway
        // This depends on whether an Order object is created and an ID assigned before failure.
        // Based on current PaymentProcessor, an Order is created but not saved before auth.
        // If it were saved, we'd expect a save call here.
        // For this test, we assert it's NOT called again after failure if no ID was set.
        $this->orderRepositoryMock->expects($this->never()) // Or $this->atMost(1) if initial save was there
            ->method('save');

        $this->paymentProcessor->processPayment($paymentData);
    }

    public function testProcessPaymentGatewayAuthorizationNetworkError(): void
    {
        $paymentData = $this->getValidPaymentData();
        $this->expectException(GatewayException::class);
        $this->expectExceptionMessage('Gateway network error during auth.');

        $this->paymentGatewayMock->expects($this->once())
            ->method('authorizePayment')
            ->willThrowException(new GatewayException('Gateway network error during auth.', 503));

        $this->paymentProcessor->processPayment($paymentData);
    }

    public function testProcessPaymentGatewayCaptureDeclined(): void
    {
        $paymentData = $this->getValidPaymentData();
        $this->expectException(GatewayException::class);
        $this->expectExceptionMessage('Capture failed.');

        $this->paymentGatewayMock->expects($this->once())
            ->method('authorizePayment')
            ->willReturn(['status' => 'authorized', 'transactionId' => 'auth_789']);

        $this->paymentGatewayMock->expects($this->once())
            ->method('capturePayment')
            ->willThrowException(new GatewayException('Capture failed.', 402));

        // Order status is updated in memory, but given the current PaymentProcessor logic,
        // if the order has no ID yet (because intermediate saves are commented out),
        // the save call in the catch(GatewayException) block will be skipped.
        // Thus, we expect 'save' to NOT be called here.
        $this->orderRepositoryMock->expects($this->never()) 
            ->method('save');

        $this->paymentProcessor->processPayment($paymentData);
    }
    
    public function testProcessPaymentDatabaseSaveErrorFinal(): void
    {
        $paymentData = $this->getValidPaymentData();
        $this->expectException(PDOException::class);
        $this->expectExceptionMessage('Simulated DB error on final save.');

        $this->paymentGatewayMock->expects($this->once())
            ->method('authorizePayment')
            ->willReturn(['status' => 'authorized', 'transactionId' => 'auth_ok']);

        $this->paymentGatewayMock->expects($this->once())
            ->method('capturePayment')
            ->willReturn(['status' => 'captured', 'captureId' => 'cap_ok']);

        $this->orderRepositoryMock->expects($this->once())
            ->method('save')
            ->willThrowException(new PDOException('Simulated DB error on final save.'));

        // Notification should not be sent if DB save fails
        $this->notificationServiceMock->expects($this->never())
            ->method('sendPaymentConfirmation');
        
        $this->paymentProcessor->processPayment($paymentData);
    }

    public function testProcessPaymentNotificationServiceFailureDoesNotFailPayment(): void
    {
        $paymentData = $this->getValidPaymentData();
        $expectedOrder = new Order((float)$paymentData['amount'], (string)$paymentData['currency'], (string)$paymentData['cardNumber']);
        $expectedOrder->setId(2);
        $expectedOrder->setStatus('completed');
        $expectedOrder->setGatewayTransactionId('auth_notify_fail');

        $this->paymentGatewayMock->expects($this->once())
            ->method('authorizePayment')
            ->willReturn(['status' => 'authorized', 'transactionId' => 'auth_notify_fail']);

        $this->paymentGatewayMock->expects($this->once())
            ->method('capturePayment')
            ->willReturn(['status' => 'captured', 'captureId' => 'cap_notify_fail']);

        $this->orderRepositoryMock->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (Order $order) use ($expectedOrder) {
                $order->setId($expectedOrder->getId());
                $order->setStatus('completed');
                $order->setGatewayTransactionId('auth_notify_fail');
                return $order;
            });

        $this->notificationServiceMock->expects($this->once())
            ->method('sendPaymentConfirmation')
            ->willThrowException(new \Exception('Simulated notification error'));

        $resultOrder = $this->paymentProcessor->processPayment($paymentData);

        // Assert payment is still successful
        $this->assertInstanceOf(Order::class, $resultOrder);
        $this->assertEquals($expectedOrder->getId(), $resultOrder->getId());
        $this->assertEquals('completed', $resultOrder->getStatus());
        // error_log would have been called in PaymentProcessor, but we don't assert that here directly.
    }

    // Add more validation tests for cardNumber, expiryMonth, expiryYear, cvv, customerEmail
    /**
     * @dataProvider validationDataProvider
     */
    public function testProcessPaymentAllValidationFailures(string $field, $value, string $expectedErrorMessage): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Payment data validation failed.');

        $paymentData = $this->getValidPaymentData([$field => $value]);
        
        try {
            $this->paymentProcessor->processPayment($paymentData);
        } catch (ValidationException $e) {
            $this->assertArrayHasKey($field, $e->getErrors());
            $this->assertEquals($expectedErrorMessage, $e->getErrors()[$field]);
            throw $e;
        }
    }

    public function validationDataProvider(): array
    {
        return [
            'invalid amount string' => ['amount', 'not-a-number', 'A positive amount is required.'],
            'zero amount' => ['amount', 0, 'A positive amount is required.'],
            'negative amount' => ['amount', -100, 'A positive amount is required.'],
            'empty amount' => ['amount', '', 'A positive amount is required.'],
            'invalid currency short' => ['currency', 'US', 'A valid 3-letter currency code is required.'],
            'invalid currency long' => ['currency', 'USDD', 'A valid 3-letter currency code is required.'],
            'empty currency' => ['currency', '', 'A valid 3-letter currency code is required.'],
            'empty card number' => ['cardNumber', '', 'Card number is required.'],
            'empty expiry month' => ['expiryMonth', '', 'Expiry month is required.'],
            'empty expiry year' => ['expiryYear', '', 'Expiry year is required.'],
            'empty cvv' => ['cvv', '', 'CVV is required.'],
            'empty customer email' => ['customerEmail', '', 'A valid customer email is required.'],
            'invalid customer email' => ['customerEmail', 'not-an-email', 'A valid customer email is required.'],
        ];
    }
} 