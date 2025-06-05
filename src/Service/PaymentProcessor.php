<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Order;
use App\Exception\GatewayException;
use App\Exception\ValidationException;
use App\Gateway\ThirdPartyPaymentGateway;
use App\Repository\OrderRepository;
use PDOException;

class PaymentProcessor
{
    private OrderRepository $orderRepository;
    private ThirdPartyPaymentGateway $paymentGateway;
    private NotificationService $notificationService;

    public function __construct(
        OrderRepository $orderRepository,
        ThirdPartyPaymentGateway $paymentGateway,
        NotificationService $notificationService
    ) {
        $this->orderRepository = $orderRepository;
        $this->paymentGateway = $paymentGateway;
        $this->notificationService = $notificationService;
    }


    public function processPayment(array $paymentData): Order
    {
        $this->validatePaymentData($paymentData);

        $order = new Order(
            (float)$paymentData['amount'],
            (string)$paymentData['currency'],
            (string)$paymentData['cardNumber']
        );
        $order->setStatus('pending_authorization');

        try {
            $cardDetails = [
                'number' => (string)$paymentData['cardNumber'],
                'expiryMonth' => (string)$paymentData['expiryMonth'],
                'expiryYear' => (string)$paymentData['expiryYear'],
                'cvv' => (string)$paymentData['cvv'],
            ];

            $authResponse = $this->paymentGateway->authorizePayment(
                $order->getAmount(),
                $order->getCurrency(),
                $cardDetails
            );

            $order->setGatewayTransactionId($authResponse['transactionId']);
            $order->setStatus('authorized');

            $captureResponse = $this->paymentGateway->capturePayment(
                $authResponse['transactionId'],
                $order->getAmount()
            );

            $order->setStatus('completed');
            $persistedOrder = $this->orderRepository->save($order);

            try {
                $this->notificationService->sendPaymentConfirmation($persistedOrder, (string)$paymentData['customerEmail']);
            } catch (\Exception $e) {
            }

            return $persistedOrder;

        } catch (GatewayException $e) {
            $order->setStatus('failed_gateway');
            if ($order->getId()) {
                $this->orderRepository->save($order);
            }
            throw $e;
        } catch (PDOException $e) {
            $order->setStatus('failed_internal_db');
            throw $e;
        } catch (\Throwable $e) {
            $order->setStatus('failed_unexpected');
            if ($order->getId()) {
                 $this->orderRepository->save($order);
            }
            throw $e;
        }
    }


    private function validatePaymentData(array $data): void
    {
        $errors = [];
        if (empty($data['amount']) || !is_numeric($data['amount']) || (float)$data['amount'] <= 0) {
            $errors['amount'] = 'A positive amount is required.';
        }
        if (empty($data['currency']) || !is_string($data['currency']) || strlen($data['currency']) !== 3) {
            $errors['currency'] = 'A valid 3-letter currency code is required.';
        }
        if (empty($data['cardNumber']) || !is_string($data['cardNumber'])) {
            $errors['cardNumber'] = 'Card number is required.';
        }
        if (empty($data['expiryMonth']) || !is_string($data['expiryMonth'])) {
            $errors['expiryMonth'] = 'Expiry month is required.';
        }
        if (empty($data['expiryYear']) || !is_string($data['expiryYear'])) {
            $errors['expiryYear'] = 'Expiry year is required.';
        }
        if (empty($data['cvv']) || !is_string($data['cvv'])) {
            $errors['cvv'] = 'CVV is required.';
        }
        if (empty($data['customerEmail']) || !filter_var($data['customerEmail'], FILTER_VALIDATE_EMAIL)) {
            $errors['customerEmail'] = 'A valid customer email is required.';
        }

        if (!empty($errors)) {
            throw new ValidationException("Payment data validation failed.", $errors);
        }
    }
}
