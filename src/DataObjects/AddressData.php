<?php

declare(strict_types=1);

namespace SumsubSdk\Sumsub\DataObjects;

/**
 * Address data
 */
readonly class AddressData
{
    public function __construct(
        public ?string $country = null,
        public ?string $postCode = null,
        public ?string $town = null,
        public ?string $street = null,
        public ?string $subStreet = null,
        public ?string $state = null,
        public ?string $buildingNumber = null,
        public ?string $flatNumber = null,
        public ?array $rawData = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            country: $data['country'] ?? null,
            postCode: $data['postCode'] ?? null,
            town: $data['town'] ?? null,
            street: $data['street'] ?? null,
            subStreet: $data['subStreet'] ?? null,
            state: $data['state'] ?? null,
            buildingNumber: $data['buildingNumber'] ?? null,
            flatNumber: $data['flatNumber'] ?? null,
            rawData: $data,
        );
    }

    /**
     * Get formatted address string
     */
    public function getFormatted(): string
    {
        $parts = array_filter([
            $this->street,
            $this->buildingNumber,
            $this->flatNumber,
            $this->town,
            $this->state,
            $this->postCode,
            $this->country,
        ]);

        return implode(', ', $parts);
    }
}

