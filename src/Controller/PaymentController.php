<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Request;
use App\Service\PaymentProcessor;
use App\Exception\ValidationException; // Already used in index.php, good to have here too
use App\Exception\GatewayException;    // Already used in index.php
use PDOException;                    // Already used implicitly via PaymentProcessor

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
            // This catch block might be redundant if index.php handles it,
            // but can be useful if controller is used elsewhere or for specific logging.
            throw $e; // Re-throw to be caught by index.php or a global error handler
        } catch (GatewayException $e) {
            throw $e; // Re-throw
        } catch (PDOException $e) {
            // In a real app, log this critical error in detail.
            // error_log("PaymentController DB Error: " . $e->getMessage());
            throw $e; // Re-throw, to be caught as a generic Throwable in index.php or specific handler
        } catch (\Throwable $e) {
            // error_log("PaymentController Unexpected Error: " . $e->getMessage());
            throw $e; // Re-throw
        }
    }
} 