<?php

declare(strict_types=1);

namespace App\Exception;

class ValidationException extends \InvalidArgumentException
{
    private array $errors;

    public function __construct(string $message = "Validation Failed", array $errors = [], int $code = 422, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
} 