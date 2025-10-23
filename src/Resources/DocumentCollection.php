<?php

declare(strict_types=1);

namespace SumsubSdk\Sumsub\Resources;

use SumsubSdk\Sumsub\DataObjects\DocumentData;

/**
 * Collection of documents
 */
class DocumentCollection
{
    /** @var DocumentResource[] */
    private array $documents = [];

    public function __construct(array $documents = [])
    {
        foreach ($documents as $document) {
            if ($document instanceof DocumentData) {
                $this->documents[] = DocumentResource::make($document);
            } elseif ($document instanceof DocumentResource) {
                $this->documents[] = $document;
            } elseif (is_array($document)) {
                $this->documents[] = DocumentResource::fromArray($document);
            }
        }
    }

    public static function make(array $documents): self
    {
        return new self($documents);
    }

    /**
     * Convert collection to array
     */
    public function toArray(): array
    {
        return array_map(
            fn(DocumentResource $doc) => $doc->toArray(),
            $this->documents
        );
    }

    /**
     * Get total count of documents
     */
    public function count(): int
    {
        return count($this->documents);
    }

    /**
     * Filter approved documents only
     */
    public function getApproved(): self
    {
        return new self(array_filter(
            $this->documents,
            fn(DocumentResource $doc) => $doc->getData()->isApproved()
        ));
    }

    /**
     * Filter rejected documents only
     */
    public function getRejected(): self
    {
        return new self(array_filter(
            $this->documents,
            fn(DocumentResource $doc) => $doc->getData()->isRejected()
        ));
    }

    /**
     * Filter documents by type
     */
    public function getByType(string $type): self
    {
        return new self(array_filter(
            $this->documents,
            fn(DocumentResource $doc) => $doc->getData()->docSetType === $type
        ));
    }

    /**
     * Get all document resources
     */
    public function all(): array
    {
        return $this->documents;
    }
}

