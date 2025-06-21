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


    public function save(Order $order): Order
    {
        $sql = $order->getId() ? "UPDATE orders SET ..." : "INSERT INTO orders (amount, currency, card_number_last4, status, gateway_transaction_id, created_at, updated_at) VALUES (:amount, :currency, :card_number_last4, :status, :gateway_transaction_id, :created_at, :updated_at)";

        $params = [
            ':amount' => $order->getAmount(),
            ':currency' => $order->getCurrency(),
            ':status' => $order->getStatus(),
            ':gateway_transaction_id' => $order->getGatewayTransactionId(),
        ];

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            if (!$order->getId()) {
                $order->setId((int)$this->db->lastInsertId());
            }
        } catch (PDOException $e) {
            throw $e;
        }

        return $order;
    }
}
