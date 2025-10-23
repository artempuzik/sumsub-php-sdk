<?php

declare(strict_types=1);

namespace SumsubSdk\Sumsub\DataObjects;

use SumsubSdk\Sumsub\Enums\ReviewStatus;
use SumsubSdk\Sumsub\Enums\ReviewAnswer;

/**
 * Review data
 */
readonly class ReviewData
{
    public function __construct(
        public ?string $reviewId = null,
        public ?string $attemptId = null,
        public ?int $attemptCnt = null,
        public ?string $levelName = null,
        public ?string $createDate = null,
        public ?string $reviewDate = null,
        public ?ReviewStatus $reviewStatus = null,
        public ?ReviewResultData $reviewResult = null,
        public ?int $priority = null,
        public ?array $rawData = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            reviewId: $data['reviewId'] ?? null,
            attemptId: $data['attemptId'] ?? null,
            attemptCnt: $data['attemptCnt'] ?? null,
            levelName: $data['levelName'] ?? null,
            createDate: $data['createDate'] ?? null,
            reviewDate: $data['reviewDate'] ?? null,
            reviewStatus: isset($data['reviewStatus']) ? ReviewStatus::tryFrom($data['reviewStatus']) : null,
            reviewResult: isset($data['reviewResult']) ? ReviewResultData::fromArray($data['reviewResult']) : null,
            priority: $data['priority'] ?? null,
            rawData: $data,
        );
    }

    /**
     * Check if review is completed
     */
    public function isCompleted(): bool
    {
        return $this->reviewStatus === ReviewStatus::COMPLETED;
    }

    /**
     * Check if review is pending
     */
    public function isPending(): bool
    {
        return $this->reviewStatus?->isPending() ?? true;
    }

    /**
     * Check if review result is approved
     */
    public function isApproved(): bool
    {
        return $this->reviewResult?->isApproved() ?? false;
    }

    /**
     * Check if review result is rejected
     */
    public function isRejected(): bool
    {
        return $this->reviewResult?->isRejected() ?? false;
    }
}

