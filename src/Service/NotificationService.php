<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Order;

class NotificationService
{
    private bool $throwExceptionOnSend = false;
    private array $sentNotifications = [];

    /**
     * Simulates sending a payment confirmation notification.
     *
     * @param Order $order
     * @param string $recipientEmail
     * @return bool True if sent successfully, false otherwise.
     * @throws \Exception If forced to throw by test configuration.
     */
    public function sendPaymentConfirmation(Order $order, string $recipientEmail): bool
    {
        if ($this->throwExceptionOnSend) {
            $this->throwExceptionOnSend = false; // Reset for next call
            throw new \Exception("Simulated notification service error.");
        }

        // echo "NotificationService: Sending payment confirmation for order {$order->getId()} to {$recipientEmail}.\n";
        $this->sentNotifications[] = [
            'type' => 'payment_confirmation',
            'orderId' => $order->getId(),
            'recipient' => $recipientEmail,
            'amount' => $order->getAmount(),
            'currency' => $order->getCurrency(),
            'timestamp' => time()
        ];
        return true;
    }

    // --- Methods to control simulation behavior for tests ---

    public function shouldThrowExceptionOnSend(bool $throw = true): void
    {
        $this->throwExceptionOnSend = $throw;
    }

    public function getSentNotifications(): array
    {
        return $this->sentNotifications;
    }

    public function clearSentNotifications(): void
    {
        $this->sentNotifications = [];
    }
} 