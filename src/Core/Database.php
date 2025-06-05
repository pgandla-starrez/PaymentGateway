<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use PDOStatement;

/**
 * Mock Database class. In a real application, this would connect to a database.
 * For this exercise, it simulates database operations and can be used to simulate errors.
 */
class Database
{
    private bool $throwExceptionOnNextQuery = false;
    private array $storage = []; // Simple in-memory storage for simulation
    private int $lastInsertId = 0;

    public function __construct()
    {
        // In a real app: $dsn, $username, $password, $options
        // For mock: echo "Database connection established (simulated).\n";
    }

    /**
     * Simulate preparing a query. 
     * In a real PDO wrapper, this would return a PDOStatement.
     * Here, it returns a mock statement or self for chaining simple queries.
     */
    public function prepare(string $sql): self // Simplified for mock
    {
        if ($this->throwExceptionOnNextQuery) {
            $this->throwExceptionOnNextQuery = false;
            throw new PDOException("Simulated database error on prepare.");
        }
        // Simulate preparing a statement
        // echo "Preparing query: {$sql}\n";
        return $this; // Return self to chain execute for this mock
    }

    /**
     * Simulate executing a prepared statement.
     * In a real PDO wrapper, this would be on PDOStatement.
     */
    public function execute(array $params = []): bool
    {
        if ($this->throwExceptionOnNextQuery) {
            $this->throwExceptionOnNextQuery = false;
            throw new PDOException("Simulated database error on execute.");
        }

        // Simulate parameter binding and execution
        // echo "Executing query with params: " . json_encode($params) . "\n";
        
        // Basic simulation for an INSERT-like operation based on params
        if (!empty($params[':amount']) && !empty($params[':currency'])) {
            $this->lastInsertId++;
            $this->storage[$this->lastInsertId] = $params;
        }
        return true;
    }

    public function lastInsertId(): string
    {
        if ($this->throwExceptionOnNextQuery) {
            throw new PDOException("Simulated database error on lastInsertId.");
        }
        return (string)$this->lastInsertId;
    }

    /**
     * Method to control mock behavior for testing error conditions.
     */
    public function shouldThrowExceptionOnNextQuery(bool $throw = true): void
    {
        $this->throwExceptionOnNextQuery = $throw;
    }

    /**
     * Retrieve a record for simulation/assertion purposes
     */
    public function findRecord(int $id): ?array
    {
        return $this->storage[$id] ?? null;
    }

    /**
     * Clear storage for test isolation
     */
    public function clearStorage(): void
    {
        $this->storage = [];
        $this->lastInsertId = 0;
    }

    // Add other PDO methods as needed, simplified for mock
    // public function query(string $sql) { ... }
    // public function fetch() { ... }
    // public function fetchAll() { ... }
} 