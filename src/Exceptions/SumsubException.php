<?php

declare(strict_types=1);

namespace SumsubSdk\Sumsub\Exceptions;

use Exception;

/**
 * Base Sumsub exception
 */
class SumsubException extends Exception
{
    protected ?string $correlationId = null;

    /**
     * Set Sumsub correlation ID for debugging
     */
    public function setCorrelationId(string $correlationId): self
    {
        $this->correlationId = $correlationId;
        return $this;
    }

    /**
     * Get Sumsub correlation ID
     */
    public function getCorrelationId(): ?string
    {
        return $this->correlationId;
    }
}

