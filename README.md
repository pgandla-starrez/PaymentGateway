# Payment Gateway API

## Objective

The goal of this exercise is to assess your ability to understand existing code and write comprehensive unit tests for it. You will be working with a simplified API endpoint that processes payments. The existing code does not have any unit tests.

## Scenario

The application has an API endpoint (`POST /payment`) that simulates processing a payment. This involves:
1.  Validating the incoming request data (e.g., amount, currency, card details).
2.  Calling a `PaymentProcessor` service.
3.  The `PaymentProcessor` interacts with:
    *   A `ThirdPartyPaymentGateway` to authorize and capture the payment.
    *   An `OrderRepository` to save payment/order details to a "database" (simulated).
    *   A `NotificationService` to send a confirmation (simulated).
4.  Returning a JSON response indicating success or failure.

## Your Task

Your task is to write unit tests for the `PaymentProcessor` service located in `src/Service/PaymentProcessor.php`.

**Key areas to cover in your tests:**

1.  **Successful Payment:**
    *   Test the scenario where the payment is processed successfully.
    *   Ensure all dependent services (`ThirdPartyPaymentGateway`, `OrderRepository`, `NotificationService`) are called with the correct parameters.
2.  **Input Validation Failures:**
    *   The `PaymentProcessor` should ideally validate its inputs or rely on validated inputs. For this exercise, assume basic validation might occur before or at the beginning of the `processPayment` method. If not explicitly present, you can suggest where it should be or test how the system behaves with invalid data.
    *   (Optional, if you add validation logic) Test scenarios where input data is invalid (e.g., missing amount, invalid currency).
3.  **Third-Party Gateway Failures:**
    *   Test how the `PaymentProcessor` handles errors from the `ThirdPartyPaymentGateway` (e.g., payment declined, gateway unavailable).
4.  **Database/Repository Failures:**
    *   Test how the `PaymentProcessor` handles errors during data persistence (e.g., database connection error, failed to save order).
5.  **Notification Service Failures (Optional but good to consider):**
    *   Test how the `PaymentProcessor` behaves if the `NotificationService` fails. Does it affect the overall payment status?

**Focus on:**

*   **Mocking:** Appropriately mock dependencies to isolate the `PaymentProcessor` unit.
*   **Assertions:** Make clear and concise assertions to verify the expected outcomes and interactions.
*   **Test Coverage:** Aim for good coverage of the different logic paths and scenarios within the `PaymentProcessor`.
*   **Readability:** Write clean, readable, and maintainable tests.

## Setup

1.  Clone this repository.
2.  Run `composer install` to install dependencies (PHPUnit).
3.  Familiarize yourself with the code in the `src` directory.
4.  The main application logic to test is in `src/Service/PaymentProcessor.php`.
5.  You will need to create your test files within the `tests` directory. You can follow a structure like `tests/Service/PaymentProcessorTest.php`.
6.  A basic `phpunit.xml.dist` is provided. You can copy it to `phpunit.xml` and customize it if needed.
7.  Run tests using the command: `composer test` or `./vendor/bin/phpunit`.

## Expected Deliverables

*   A Git patch file or a link to your forked repository with your new unit tests.
*   Your tests should pass when executed with PHPUnit.

Good luck!
