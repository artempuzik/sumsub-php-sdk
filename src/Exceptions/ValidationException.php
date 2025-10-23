<?php

declare(strict_types=1);

namespace SumsubSdk\Sumsub\Exceptions;

/**
 * Data validation exception
 */
class ValidationException extends SumsubException
{
    public function __construct(
        string $message,
        private array $errors = []
    ) {
        parent::__construct($message);
    }

    /**
     * Get validation errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}

