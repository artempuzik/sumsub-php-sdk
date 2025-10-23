<?php

declare(strict_types=1);

namespace SumsubSdk\Sumsub\Validators;

use SumsubSdk\Sumsub\Exceptions\ValidationException;

/**
 * Validator for webhook payloads
 */
class WebhookValidator
{
    public function __construct(
        private string $secretKey
    ) {}

    /**
     * Validate webhook signature
     *
     * @throws ValidationException
     */
    public function validateSignature(
        string $payload,
        string $receivedSignature,
        string $algorithm = 'sha256'
    ): bool {
        $expectedSignature = hash_hmac($algorithm, $payload, $this->secretKey);

        if (!hash_equals($expectedSignature, $receivedSignature)) {
            throw new ValidationException('Invalid webhook signature');
        }

        return true;
    }

    /**
     * Validate webhook payload structure
     *
     * @throws ValidationException
     */
    public function validatePayload(array $payload): bool
    {
        $requiredFields = ['type', 'applicantId', 'externalUserId'];

        foreach ($requiredFields as $field) {
            if (!isset($payload[$field])) {
                throw new ValidationException(
                    "Missing required field: {$field}",
                    ['missing_fields' => [$field]]
                );
            }
        }

        return true;
    }
}

