<?php

declare(strict_types=1);

namespace SumsubSdk\Sumsub\Exceptions;

/**
 * API request exception
 */
class ApiException extends SumsubException
{
    public function __construct(
        string $message,
        private int $statusCode = 0,
        private ?array $responseData = null,
        ?string $correlationId = null
    ) {
        parent::__construct($message, $statusCode);

        if ($correlationId) {
            $this->setCorrelationId($correlationId);
        }
    }

    /**
     * Get HTTP status code
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Get response data from API
     */
    public function getResponseData(): ?array
    {
        return $this->responseData;
    }
}

