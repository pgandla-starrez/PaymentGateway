<?php

declare(strict_types=1);

namespace App\Entity;

class Order
{
    private ?int $id = null;
    private float $amount;
    private string $currency;
    private string $cardNumber;
    private string $status; // e.g., pending, completed, failed
    private ?string $gatewayTransactionId = null;
    private \DateTimeImmutable $createdAt;
    private \DateTimeImmutable $updatedAt;

    public function __construct(float $amount, string $currency, string $cardNumber)
    {
        $this->amount = $amount;
        $this->currency = $currency;
        // In a real app, never store raw card number. This is for exercise simplification.
        // Consider storing only last 4 digits or a token.
        $this->cardNumber = $cardNumber; 
        $this->status = 'pending';
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getCardNumber(): string
    {
        // This is a simplified representation. 
        // In real systems, card numbers are handled with extreme care (e.g., tokenization).
        return $this->cardNumber;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getGatewayTransactionId(): ?string
    {
        return $this->gatewayTransactionId;
    }

    public function setGatewayTransactionId(?string $gatewayTransactionId): void
    {
        $this->gatewayTransactionId = $gatewayTransactionId;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'amount' => $this->amount,
            'currency' => $this->currency,
            // 'cardNumber' => '**** **** **** ' . substr($this->cardNumber, -4), // Example of masking
            'status' => $this->status,
            'gatewayTransactionId' => $this->gatewayTransactionId,
            'createdAt' => $this->createdAt->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updatedAt->format('Y-m-d H:i:s'),
        ];
    }
} 