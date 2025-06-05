<?php

declare(strict_types=1);

namespace App\Gateway;

use App\Exception\GatewayException;

class ThirdPartyPaymentGateway
{
    private bool $simulateSuccess = true;
    private string $simulateDeclineReason = '';
    private bool $simulateNetworkError = false;


    public function authorizePayment(float $amount, string $currency, array $cardDetails): array
    {
        if ($this->simulateNetworkError) {
            $this->resetSimulations();
            throw new GatewayException("Simulated network error connecting to payment gateway.", 503);
        }

        if (!$this->simulateSuccess) {
            $reason = $this->simulateDeclineReason ?: 'Payment declined by gateway.';
            $this->resetSimulations();
            throw new GatewayException($reason, 402);
        }

        if (empty($cardDetails['number']) || empty($cardDetails['expiryMonth']) || empty($cardDetails['expiryYear']) || empty($cardDetails['cvv'])) {
            throw new GatewayException("Gateway validation: Missing card details.", 400);
        }

        if ($amount < 0.50) {
             throw new GatewayException("Gateway validation: Amount too low.", 400);
        }

        $this->resetSimulations();
        return [
            'status' => 'authorized',
            'transactionId' => 'gw_auth_' . bin2hex(random_bytes(8)),
            'amount' => $amount,
            'currency' => $currency
        ];
    }


    public function capturePayment(string $transactionId, float $amount): array
    {
        if ($this->simulateNetworkError) {
            $this->resetSimulations();
            throw new GatewayException("Simulated network error during capture.", 503);
        }

        if (!$this->simulateSuccess) {
            $reason = $this->simulateDeclineReason ?: 'Capture failed at gateway.';
            $this->resetSimulations();
            throw new GatewayException($reason, 402);
        }

        $this->resetSimulations();
        return [
            'status' => 'captured',
            'captureId' => 'gw_cap_' . bin2hex(random_bytes(8)),
            'originalTransactionId' => $transactionId,
            'capturedAmount' => $amount
        ];
    }



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
        $this->simulateSuccess = false;
        $this->simulateNetworkError = true;
    }

    private function resetSimulations(): void
    {

    }
}
