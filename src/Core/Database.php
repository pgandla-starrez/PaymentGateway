<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use PDOStatement;


class Database
{
    private bool $throwExceptionOnNextQuery = false;
    private array $storage = [];
    private int $lastInsertId = 0;

    public function __construct()
    {
    }


    public function prepare(string $sql): self
    {
        if ($this->throwExceptionOnNextQuery) {
            $this->throwExceptionOnNextQuery = false;
            throw new PDOException("Simulated database error on prepare.");
        }
        return $this;
    }


    public function execute(array $params = []): bool
    {
        if ($this->throwExceptionOnNextQuery) {
            $this->throwExceptionOnNextQuery = false;
            throw new PDOException("Simulated database error on execute.");
        }

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

    public function shouldThrowExceptionOnNextQuery(bool $throw = true): void
    {
        $this->throwExceptionOnNextQuery = $throw;
    }

    public function findRecord(int $id): ?array
    {
        return $this->storage[$id] ?? null;
    }

    public function clearStorage(): void
    {
        $this->storage = [];
        $this->lastInsertId = 0;
    }

}
