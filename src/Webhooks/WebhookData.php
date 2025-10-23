<?php

declare(strict_types=1);

namespace SumsubSdk\Sumsub\Webhooks;

use SumsubSdk\Sumsub\Enums\WebhookType;
use SumsubSdk\Sumsub\Enums\ReviewAnswer;
use SumsubSdk\Sumsub\DataObjects\ApplicantData;

/**
 * Webhook payload data
 */
readonly class WebhookData
{
    public function __construct(
        public WebhookType $type,
        public string $applicantId,
        public string $externalUserId,
        public ?string $inspectionId = null,
        public ?string $correlationId = null,
        public ?string $reviewStatus = null,
        public ?ReviewAnswer $reviewAnswer = null,
        public ?string $applicantType = null,
        public ?string $createdAt = null,
        public ?array $reviewResult = null,
        public ?array $rawPayload = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            type: WebhookType::from($data['type']),
            applicantId: $data['applicantId'],
            externalUserId: $data['externalUserId'],
            inspectionId: $data['inspectionId'] ?? null,
            correlationId: $data['correlationId'] ?? null,
            reviewStatus: $data['reviewStatus'] ?? null,
            reviewAnswer: isset($data['reviewResult']['reviewAnswer'])
                ? ReviewAnswer::tryFrom($data['reviewResult']['reviewAnswer'])
                : null,
            applicantType: $data['applicantType'] ?? null,
            createdAt: $data['createdAt'] ?? null,
            reviewResult: $data['reviewResult'] ?? null,
            rawPayload: $data,
        );
    }

    /**
     * Check if applicant was approved
     */
    public function isApproved(): bool
    {
        return $this->reviewAnswer === ReviewAnswer::GREEN;
    }

    /**
     * Check if applicant was rejected
     */
    public function isRejected(): bool
    {
        return $this->reviewAnswer === ReviewAnswer::RED;
    }

    /**
     * Check if requires additional review
     */
    public function requiresReview(): bool
    {
        return $this->reviewAnswer === ReviewAnswer::YELLOW;
    }

    /**
     * Check if review is completed
     */
    public function isReviewCompleted(): bool
    {
        return $this->type->isReview() && $this->reviewStatus === 'completed';
    }
}

