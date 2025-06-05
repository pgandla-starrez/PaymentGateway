<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Request;
use App\Service\PaymentProcessor;
use App\Exception\ValidationException;
use App\Exception\GatewayException;
use PDOException;

class PaymentController
{
    private PaymentProcessor $paymentProcessor;

    public function __construct(PaymentProcessor $paymentProcessor)
    {
        $this->paymentProcessor = $paymentProcessor;
    }

    /**
     * Handles the API request to process a payment.
     *
     * @param Request $request
     * @return array ['statusCode' => int, 'data' => array]
     */
    public function processPayment(Request $request): array
    {
        $paymentData = $request->all();

        try {
            $order = $this->paymentProcessor->processPayment($paymentData);
            return [
                'statusCode' => 200,
                'data' => [
                    'status' => 'success',
                    'message' => 'Payment processed successfully.',
                    'order' => $order->toArray()
                ]
            ];
        } catch (ValidationException $e) {
            throw $e;
        } catch (GatewayException $e) {
            throw $e;
        } catch (PDOException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw $e;
        }
    }
}
