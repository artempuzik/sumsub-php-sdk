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
        public ?string $number,
        public ?string $validUntil,
        public string $idDocType,
        public ?string $imageId = null,
        public ?string $docSetType = null,
        public ?string $country = null,
        public ?ReviewAnswer $reviewAnswer = null,
        public ?string $attemptId = null,
        public ?array $rawData = null,
        public ?string $base64Image = null,
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
            imageId: $data['imageId'] ?? null,
            docSetType: $data['docSetType'] ?? null,
            idDocType: $data['idDocType'] ?? null,
            country: $data['country'] ?? null,
            reviewAnswer: $reviewAnswer,
            attemptId: $data['attemptId'] ?? null,
            rawData: $data,
            number: $data['number'] ?? null,
            validUntil: $data['validUntil'] ?? null,
            base64Image: $data['base64Image'] ?? null,
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

    /**
     * Get document type number
     */
    public function getTypeNumber(): string
    {
        return match($this->idDocType) {
            DocumentType::ID_CARD->value => '2',
            DocumentType::PASSPORT->value => '1',
            DocumentType::DRIVERS->value => '3',
            DocumentType::RESIDENCE_PERMIT->value => '4',
            default => '0',
        };
    }
}

