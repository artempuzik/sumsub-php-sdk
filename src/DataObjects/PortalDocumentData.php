<?php

declare(strict_types=1);

namespace SumsubSdk\Sumsub\DataObjects;

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
        public ?string $front,
        public ?string $back,
        public ?string $face,
    ) {}

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

