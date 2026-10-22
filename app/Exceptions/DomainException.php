<?php

namespace App\Exceptions;

use RuntimeException;

class DomainException extends RuntimeException
{
    /**
     * @param array<string, string|array<int, string>> $errors
     */
    public function __construct(
        string $message,
        public readonly int $statusCode = 422,
        public readonly array $errors = []
    ) {
        parent::__construct($message);
    }
}
