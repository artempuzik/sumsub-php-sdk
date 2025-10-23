<?php

declare(strict_types=1);

namespace SumsubSdk\Sumsub\DataObjects;

use SumsubSdk\Sumsub\Enums\ReviewAnswer;

/**
 * Review result data
 */
readonly class ReviewResultData
{
    public function __construct(
        public ?ReviewAnswer $reviewAnswer = null,
        public ?string $moderationComment = null,
        public ?string $clientComment = null,
        public ?string $reviewRejectType = null,
        public ?array $rejectLabels = null,
        public ?array $rawData = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            reviewAnswer: isset($data['reviewAnswer']) ? ReviewAnswer::tryFrom($data['reviewAnswer']) : null,
            moderationComment: $data['moderationComment'] ?? null,
            clientComment: $data['clientComment'] ?? null,
            reviewRejectType: $data['reviewRejectType'] ?? null,
            rejectLabels: $data['rejectLabels'] ?? null,
            rawData: $data,
        );
    }

    /**
     * Check if result is approved (GREEN)
     */
    public function isApproved(): bool
    {
        return $this->reviewAnswer === ReviewAnswer::GREEN;
    }

    /**
     * Check if result is rejected (RED)
     */
    public function isRejected(): bool
    {
        return $this->reviewAnswer === ReviewAnswer::RED;
    }

    /**
     * Check if requires additional review (YELLOW)
     */
    public function requiresReview(): bool
    {
        return $this->reviewAnswer === ReviewAnswer::YELLOW;
    }
}

