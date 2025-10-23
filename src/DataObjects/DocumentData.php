<?php

declare(strict_types=1);

namespace SumsubSdk\Sumsub\DataObjects;

use SumsubSdk\Sumsub\Enums\DocumentType;
use SumsubSdk\Sumsub\Enums\ReviewAnswer;

/**
 * Document data
 */
readonly class DocumentData
{
    public function __construct(
        public string $imageId,
        public string $docSetType,
        public ?string $idDocType = null,
        public ?string $country = null,
        public ?ReviewAnswer $reviewAnswer = null,
        public ?string $attemptId = null,
        public ?array $rawData = null,
    ) {}

    public static function fromArray(array $data): self
    {
        $reviewAnswer = null;
        if (isset($data['reviewResult']['reviewAnswer'])) {
            $reviewAnswer = ReviewAnswer::tryFrom($data['reviewResult']['reviewAnswer']);
        } elseif (isset($data['imageReviewResult']['reviewAnswer'])) {
            $reviewAnswer = ReviewAnswer::tryFrom($data['imageReviewResult']['reviewAnswer']);
        }

        return new self(
            imageId: (string)$data['imageId'],
            docSetType: $data['docSetType'],
            idDocType: $data['idDocType'] ?? null,
            country: $data['country'] ?? null,
            reviewAnswer: $reviewAnswer,
            attemptId: $data['attemptId'] ?? null,
            rawData: $data,
        );
    }

    /**
     * Check if document is approved
     */
    public function isApproved(): bool
    {
        return $this->reviewAnswer === ReviewAnswer::GREEN;
    }

    /**
     * Check if document is rejected
     */
    public function isRejected(): bool
    {
        return $this->reviewAnswer === ReviewAnswer::RED;
    }

    /**
     * Check if document requires review
     */
    public function requiresReview(): bool
    {
        return $this->reviewAnswer === ReviewAnswer::YELLOW;
    }
}

