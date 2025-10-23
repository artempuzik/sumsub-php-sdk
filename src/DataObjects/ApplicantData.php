<?php

declare(strict_types=1);

namespace SumsubSdk\Sumsub\DataObjects;

use SumsubSdk\Sumsub\Enums\ApplicantType;
use SumsubSdk\Sumsub\Enums\ReviewStatus;
use SumsubSdk\Sumsub\Enums\ReviewAnswer;

/**
 * Applicant data object
 */
readonly class ApplicantData
{
    public function __construct(
        public string $id,
        public string $externalUserId,
        public string $createdAt,
        public ?string $inspectionId = null,
        public ?ApplicantType $type = null,
        public ?string $lang = null,
        public ?string $key = null,
        public ?string $clientId = null,
        public ?string $applicantPlatform = null,
        public ?string $email = null,
        public ?ApplicantInfoData $info = null,
        public ?ApplicantInfoData $fixedInfo = null,
        public ?ReviewData $review = null,
        public ?array $agreement = null,
        public ?array $requiredIdDocs = null,
        public ?array $rawData = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            externalUserId: $data['externalUserId'],
            createdAt: $data['createdAt'],
            inspectionId: $data['inspectionId'] ?? null,
            type: isset($data['type']) ? ApplicantType::tryFrom($data['type']) : null,
            lang: $data['lang'] ?? null,
            key: $data['key'] ?? null,
            clientId: $data['clientId'] ?? null,
            applicantPlatform: $data['applicantPlatform'] ?? null,
            email: $data['email'] ?? null,
            info: isset($data['info']) && is_array($data['info']) && !empty($data['info'])
                ? ApplicantInfoData::fromArray($data['info'])
                : null,
            fixedInfo: isset($data['fixedInfo']) && is_array($data['fixedInfo']) && !empty($data['fixedInfo'])
                ? ApplicantInfoData::fromArray($data['fixedInfo'])
                : null,
            review: isset($data['review']) ? ReviewData::fromArray($data['review']) : null,
            agreement: $data['agreement'] ?? null,
            requiredIdDocs: $data['requiredIdDocs'] ?? null,
            rawData: $data,
        );
    }

    /**
     * Check if applicant is verified (approved)
     */
    public function isVerified(): bool
    {
        return $this->review?->isApproved() ?? false;
    }

    /**
     * Check if applicant is rejected
     */
    public function isRejected(): bool
    {
        return $this->review?->isRejected() ?? false;
    }

    /**
     * Check if verification is pending
     */
    public function isPending(): bool
    {
        return $this->review?->isPending() ?? true;
    }
}

