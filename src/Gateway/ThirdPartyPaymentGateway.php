<?php

declare(strict_types=1);

namespace App\Gateway;

use App\Exception\GatewayException;

class ThirdPartyPaymentGateway
{
    // Simulate different gateway behaviors for testing
    private bool $simulateSuccess = true;
    private string $simulateDeclineReason = '';
    private bool $simulateNetworkError = false;

    /**
     * Simulates authorizing a payment with a third-party gateway.
     *
     * @param float $amount
     * @param string $currency
     * @param array $cardDetails Associative array of card details (e.g., number, expiryMonth, expiryYear, cvv)
     * @return array Response from the gateway (e.g., ['status' => 'authorized', 'transactionId' => 'xyz123'])
     * @throws GatewayException If the gateway communication fails or payment is declined.
     */
    public function authorizePayment(float $amount, string $currency, array $cardDetails): array
    {
        // echo "ThirdPartyPaymentGateway: Authorizing payment for {$amount} {$currency}...\n";
        
        if ($this->simulateNetworkError) {
            $this->resetSimulations();
            throw new GatewayException("Simulated network error connecting to payment gateway.", 503);
        }

        if (!$this->simulateSuccess) {
            $reason = $this->simulateDeclineReason ?: 'Payment declined by gateway.';
            $this->resetSimulations();
            throw new GatewayException($reason, 402); // Payment Required (but failed)
        }

        // Simulate input validation by gateway (very basic)
        if (empty($cardDetails['number']) || empty($cardDetails['expiryMonth']) || empty($cardDetails['expiryYear']) || empty($cardDetails['cvv'])) {
            throw new GatewayException("Gateway validation: Missing card details.", 400);
        }

        if ($amount < 0.50) {
             throw new GatewayException("Gateway validation: Amount too low.", 400);
        }

        // Simulate a successful authorization
        $this->resetSimulations();
        return [
            'status' => 'authorized',
            'transactionId' => 'gw_auth_' . bin2hex(random_bytes(8)),
            'amount' => $amount,
            'currency' => $currency
        ];
    }

    /**
     * Simulates capturing a previously authorized payment.
     *
     * @param string $transactionId The authorization transaction ID.
     * @param float $amount The amount to capture.
     * @return array Response from the gateway (e.g., ['status' => 'captured', 'captureId' => 'cap_abc789'])
     * @throws GatewayException If the capture fails.
     */
    public function capturePayment(string $transactionId, float $amount): array
    {
        // echo "ThirdPartyPaymentGateway: Capturing payment for transaction {$transactionId}, amount {$amount}...\n";

        if ($this->simulateNetworkError) {
            $this->resetSimulations();
            throw new GatewayException("Simulated network error during capture.", 503);
        }

        if (!$this->simulateSuccess) {
            $reason = $this->simulateDeclineReason ?: 'Capture failed at gateway.';
            $this->resetSimulations();
            throw new GatewayException($reason, 402);
        }

        // Simulate a successful capture
        $this->resetSimulations();
        return [
            'status' => 'captured',
            'captureId' => 'gw_cap_' . bin2hex(random_bytes(8)),
            'originalTransactionId' => $transactionId,
            'capturedAmount' => $amount
        ];
    }

    // --- Methods to control simulation behavior for tests ---

    public function willSucceed(): void
    {
        $this->simulateSuccess = true;
        $this->simulateDeclineReason = '';
        $this->simulateNetworkError = false;
    }

    public function willDecline(string $reason = 'Payment declined by gateway.'): void
    {
        $this->simulateSuccess = false;
        $this->simulateDeclineReason = $reason;
        $this->simulateNetworkError = false;
    }

    public function willHaveNetworkError(): void
    {
        $this->simulateSuccess = false; // Network error implies not successful
        $this->simulateNetworkError = true;
    }

    private function resetSimulations(): void
    {
        // Optional: Reset to default behavior after each call, or manage explicitly in tests.
        // For now, let's reset to success by default unless another simulation is set.
        // $this->willSucceed(); 
    }
} 