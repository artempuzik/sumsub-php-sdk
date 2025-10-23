<?php

declare(strict_types=1);

namespace SumsubSdk\Sumsub\DataObjects;

/**
 * Applicant personal information
 */
readonly class ApplicantInfoData
{
    public function __construct(
        public ?string $firstName = null,
        public ?string $lastName = null,
        public ?string $middleName = null,
        public ?string $dob = null,
        public ?string $country = null,
        public ?string $nationality = null,
        public ?string $phone = null,
        public ?string $email = null,
        public ?AddressData $address = null,
        public ?array $rawData = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            firstName: $data['firstName'] ?? null,
            lastName: $data['lastName'] ?? null,
            middleName: $data['middleName'] ?? null,
            dob: $data['dob'] ?? null,
            country: $data['country'] ?? null,
            nationality: $data['nationality'] ?? null,
            phone: $data['phone'] ?? null,
            email: $data['email'] ?? null,
            address: isset($data['addresses'][0]) ? AddressData::fromArray($data['addresses'][0]) : null,
            rawData: $data,
        );
    }

    /**
     * Get formatted full name
     */
    public function getFullName(): string
    {
        return trim(implode(' ', array_filter([
            $this->firstName,
            $this->middleName,
            $this->lastName
        ])));
    }
}

