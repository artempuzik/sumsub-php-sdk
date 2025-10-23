<?php

declare(strict_types=1);

namespace SumsubSdk\Sumsub\DataObjects;

/**
 * Portal Individual Data DTO
 */
readonly class PortalIndividualData
{
    public function __construct(
        public ?string $first_name,
        public ?string $last_name,
        public ?string $date_of_birth,
        public ?string $occupation,
        public ?string $annual_income,
    ) {}

    /**
     * Create from ApplicantInfoData
     */
    public static function fromApplicantInfo(ApplicantInfoData $info): self
    {
        return new self(
            first_name: $info->firstName,
            last_name: $info->lastName,
            date_of_birth: $info->dob,
            occupation: null, // Not available in Sumsub basic data
            annual_income: null, // Not available in Sumsub basic data
        );
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return array_filter([
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'date_of_birth' => $this->date_of_birth,
            'occupation' => $this->occupation,
            'annual_income' => $this->annual_income,
        ], fn($value) => $value !== null);
    }
}

