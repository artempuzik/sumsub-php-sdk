<?php

declare(strict_types=1);

namespace SumsubSdk\Sumsub\DataObjects;

/**
 * Portal User Data DTO
 * Formatted data for external portal integration
 */
readonly class PortalUserData
{
    public function __construct(
        public string $user_xid,
        public ?string $email,
        public ?string $user_name,
        public ?PortalIndividualData $individual,
        public ?PortalAddressData $address,
        public ?PortalDocumentData $document,
    ) {}

    /**
     * Create from Sumsub applicant data
     */
    public static function fromApplicantData(ApplicantData $applicant): self
    {
        $info = $applicant->info;

        return new self(
            user_xid: $applicant->externalUserId,
            email: $info?->email,
            user_name: self::generateUsername($info),
            individual: $info ? PortalIndividualData::fromApplicantInfo($info) : null,
            address: $info?->address ? PortalAddressData::fromAddressData($info->address) : null,
            document: null, // Will be populated separately from documents
        );
    }

    /**
     * Generate username from applicant info
     */
    private static function generateUsername(?ApplicantInfoData $info): ?string
    {
        if (!$info || !$info->firstName) {
            return null;
        }

        $firstName = strtolower($info->firstName);
        $lastName = strtolower($info->lastName ?? '');

        return $firstName . ($lastName ? $lastName : '');
    }

    /**
     * Convert to array for portal
     */
    public function toArray(): array
    {
        return [
            'user_xid' => $this->user_xid,
            'email' => $this->email,
            'user_name' => $this->user_name,
            'individual' => $this->individual?->toArray(),
            'address' => $this->address?->toArray(),
            'document' => $this->document?->toArray(),
        ];
    }
}

