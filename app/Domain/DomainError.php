<?php

namespace App\Domain;

use RuntimeException;

final class DomainError extends RuntimeException
{
    public function __construct(public readonly string $errorCode, string $message, public readonly int $status = 409)
    {
        parent::__construct($message);
    }
}
