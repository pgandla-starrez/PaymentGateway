<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;
use App\Entity\Order;
use PDOException;

class OrderRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Saves an order to the database (simulated).
     * In a real application, this would involve more complex SQL and error handling.
     *
     * @param Order $order
     * @return Order The persisted order with an ID.
     * @throws PDOException If a database error occurs.
     */
    public function save(Order $order): Order
    {
        // Simulate an INSERT or UPDATE query
        $sql = $order->getId() ? "UPDATE orders SET ..." : "INSERT INTO orders (amount, currency, card_number_last4, status, gateway_transaction_id, created_at, updated_at) VALUES (:amount, :currency, :card_number_last4, :status, :gateway_transaction_id, :created_at, :updated_at)";
        
        // In a real scenario, you wouldn't store the full card number or do it this way.
        // This is simplified for the exercise.
        $params = [
            ':amount' => $order->getAmount(),
            ':currency' => $order->getCurrency(),
            // ':card_number_last4' => substr($order->getCardNumber(), -4), // Example
            ':status' => $order->getStatus(),
            ':gateway_transaction_id' => $order->getGatewayTransactionId(),
            // ':created_at' => $order->getCreatedAt()->format('Y-m-d H:i:s'),
            // ':updated_at' => $order->getUpdatedAt()->format('Y-m-d H:i:s'),
        ];

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            if (!$order->getId()) {
                $order->setId((int)$this->db->lastInsertId());
            }
        } catch (PDOException $e) {
            // Log error appropriately in a real application
            // For the exercise, rethrow to be handled by service/controller
            throw $e; 
        }

        return $order;
    }

    // Potentially: findById, findByCriteria, etc.
} 