<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Order;

class NotificationService
{
    private bool $throwExceptionOnSend = false;
    private array $sentNotifications = [];


    public function sendPaymentConfirmation(Order $order, string $recipientEmail): bool
    {
        if ($this->throwExceptionOnSend) {
            $this->throwExceptionOnSend = false;
            throw new \Exception("Simulated notification service error.");
        }

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
