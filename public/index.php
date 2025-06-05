<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Controller\PaymentController;
use App\Core\Database;
use App\Core\Request;
use App\Gateway\ThirdPartyPaymentGateway;
use App\Repository\OrderRepository;
use App\Service\NotificationService;
use App\Service\PaymentProcessor;

header("Content-Type: application/json");

$response = ['status' => 'error', 'message' => 'Invalid request'];
$statusCode = 400;

// Basic routing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_SERVER['PATH_INFO'] ?? '/') === '/payment') {
    try {
        $requestBody = json_decode(file_get_contents('php://input'), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException('Invalid JSON payload: ' . json_last_error_msg());
        }

        $request = new Request($requestBody ?? []);

        // Dependencies (simplified instantiation)
        $db = new Database(); // Simulated DB connection
        $orderRepository = new OrderRepository($db);
        $paymentGateway = new ThirdPartyPaymentGateway();
        $notificationService = new NotificationService();
        
        $paymentProcessor = new PaymentProcessor(
            $orderRepository,
            $paymentGateway,
            $notificationService
        );
        
        $controller = new PaymentController($paymentProcessor);
        $apiResponse = $controller->processPayment($request);
        
        $response = $apiResponse['data'];
        $statusCode = $apiResponse['statusCode'];

    } catch (\App\Exception\ValidationException $e) {
        $statusCode = 422; // Unprocessable Entity
        $response = ['status' => 'validation_error', 'message' => $e->getMessage(), 'errors' => $e->getErrors()];
    } catch (\App\Exception\GatewayException $e) {
        $statusCode = 503; // Service Unavailable (or specific gateway error code)
        $response = ['status' => 'gateway_error', 'message' => $e->getMessage()];
    } catch (InvalidArgumentException $e) {
        $statusCode = 400; // Bad request
        $response = ['status' => 'error', 'message' => $e->getMessage()];
    } catch (\Throwable $e) {
        // Log the error in a real application
        // error_log($e->getMessage() . "\n" . $e->getTraceAsString());
        $statusCode = 500;
        $response = ['status' => 'error', 'message' => 'An unexpected error occurred. '. $e->getMessage()];
    }
} else {
    $statusCode = 404;
    $response = ['status' => 'error', 'message' => 'Endpoint not found'];
}

http_response_code($statusCode);
echo json_encode($response); 