<?php

declare(strict_types=1);

namespace SumsubSdk\Sumsub\DataObjects;

/**
 * Portal Address Data DTO
 */
readonly class PortalAddressData
{
    public function __construct(
        public ?string $country,
        public ?string $city,
        public ?string $post_code,
        public ?string $details,
    ) {}

    /**
     * Create from AddressData
     */
    public static function fromAddressData(AddressData $address): self
    {
        // Build full address details string
        $details = trim(implode(', ', array_filter([
            $address->street,
            $address->buildingNumber ? "Building {$address->buildingNumber}" : null,
            $address->flatNumber ? "Apt {$address->flatNumber}" : null,
        ])));

        return new self(
            country: $address->country,
            city: $address->town,
            post_code: $address->postCode,
            details: $details ?: null,
        );
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return array_filter([
            'country' => $this->country,
            'city' => $this->city,
            'post_code' => $this->post_code,
            'details' => $this->details,
        ], fn($value) => $value !== null);
    }
}

