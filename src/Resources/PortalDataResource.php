<?php

declare(strict_types=1);

namespace SumsubSdk\Sumsub\Resources;

use SumsubSdk\Sumsub\DataObjects\PortalUserData;
use SumsubSdk\Sumsub\DataObjects\ApplicantData;
use SumsubSdk\Sumsub\Resources\DocumentCollection;

/**
 * Transform Sumsub data to Portal format
 */
class PortalDataResource
{
    private PortalUserData $portalData;

    private function __construct(PortalUserData $portalData)
    {
        $this->portalData = $portalData;

    }

    /**
     * Create portal data from Sumsub applicant and documents
     *
     * @param ApplicantData $aplicant User's referral code or external ID
     * @param DocumentCollection $documents Whether to include base64 encoded images
     * @return self
     */
    public static function make(
        ApplicantData $applicant,
        DocumentCollection $documents
    ): self {
        return new self(PortalUserData::fromApplicantData($applicant, $documents));
    }

    /**
     * Get portal data object
     */
    public function getData(): PortalUserData
    {
        return $this->portalData;
    }

    /**
     * Convert to array for portal
     */
    public function toArray(): array
    {
        return $this->portalData->toArray();
    }

    /**
     * Convert to JSON string
     */
    public function toJson(int $options = 0): string
    {
        return json_encode($this->toArray(), $options);
    }

    /**
     * Set portal data email
     */
    public function setEmail(string $email)
    {
        $this->portalData->setEmail($email);
    }
}
