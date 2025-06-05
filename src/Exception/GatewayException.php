<?php

declare(strict_types=1);

namespace App\Exception;

class GatewayException extends \RuntimeException
{
    // You can add specific properties related to gateway errors if needed
    // For example, a gateway-specific error code or transaction ID

    public function __construct(string $message = "Payment Gateway Error", int $code = 503, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
} 