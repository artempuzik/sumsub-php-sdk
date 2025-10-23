<?php

declare(strict_types=1);

namespace SumsubSdk\Sumsub\Resources;

use SumsubSdk\Sumsub\DataObjects\DocumentData;

/**
 * Resource for transforming document data
 */
class DocumentResource
{
    public function __construct(
        private DocumentData $document
    ) {}

    public static function make(DocumentData $document): self
    {
        return new self($document);
    }

    public static function fromArray(array $data): self
    {
        return new self(DocumentData::fromArray($data));
    }

    public function toArray(): array
    {
        return [
            'image_id' => $this->document->imageId,
            'type' => $this->document->docSetType,
            'document_type' => $this->document->idDocType,
            'country' => $this->document->country,
            'review_status' => $this->getReviewStatus(),
            'is_approved' => $this->document->isApproved(),
            'is_rejected' => $this->document->isRejected(),
            'requires_review' => $this->document->requiresReview(),
        ];
    }

    /**
     * Get review status as lowercase string
     */
    private function getReviewStatus(): ?string
    {
        if (!$this->document->reviewAnswer) {
            return null;
        }

        return strtolower($this->document->reviewAnswer->value);
    }

    /**
     * Get underlying data object
     */
    public function getData(): DocumentData
    {
        return $this->document;
    }
}

