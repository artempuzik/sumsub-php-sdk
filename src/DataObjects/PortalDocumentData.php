<?php

declare(strict_types=1);

namespace SumsubSdk\Sumsub\DataObjects;


use SumsubSdk\Sumsub\Resources\DocumentCollection;
/**
 * Portal Document Data DTO
 */
readonly class PortalDocumentData
{
    public function __construct(
        public string $type,
        public ?string $number,
        public ?string $country,
        public ?string $expiry_date,
        public ?string $front = null,
        public ?string $back = null,
        public ?string $face = null,
    ) {}

    /**
     * Create from document images (base64 encoded)
     */
    public static function make(
        DocumentData $document,
        DocumentCollection $documents
    ): self {
        return new self(
            type: self::mapDocumentType($document->idDocType),
            number: $document->number,
            country: $document->country,
            expiry_date: $document->validUntil,
            front: $documents->frontBase64(),
            back: $documents->backBase64(),
            face: $documents->faceBase64(),
        );
    }


    /**
     * Create from document images (base64 encoded)
     */
    public static function fromDocumentImages(
        string $docType,
        ?string $docNumber,
        ?string $country,
        ?string $expiryDate,
        ?string $frontImage = null,
        ?string $backImage = null,
        ?string $faceImage = null
    ): self {
        return new self(
            type: self::mapDocumentType($docType),
            number: $docNumber,
            country: $country,
            expiry_date: $expiryDate,
            front: $frontImage ? "data:image/jpeg;base64,{$frontImage}" : null,
            back: $backImage ? "data:image/jpeg;base64,{$backImage}" : null,
            face: $faceImage ? "data:image/jpeg;base64,{$faceImage}" : null,
        );
    }

    /**
     * Map Sumsub document type to portal type code
     */
    private static function mapDocumentType(string $sumsubType): string
    {
        return match(strtoupper($sumsubType)) {
            'PASSPORT' => '1',
            'ID_CARD', 'IDENTITY' => '2',
            'DRIVERS', 'DRIVERS_LICENSE' => '3',
            default => '0', // Unknown type
        };
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return array_filter([
            'type' => $this->type,
            'number' => $this->number,
            'country' => $this->country,
            'expiry_date' => $this->expiry_date,
            'front' => $this->front,
            'back' => $this->back,
            'face' => $this->face,
        ], fn($value) => $value !== null);
    }
}

