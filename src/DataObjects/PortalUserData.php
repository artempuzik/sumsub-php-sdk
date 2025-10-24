<?php

declare(strict_types=1);

namespace SumsubSdk\Sumsub\DataObjects;

use SumsubSdk\Sumsub\Resources\DocumentCollection;
/**
 * Portal User Data DTO
 * Formatted data for external portal integration
 */
class PortalUserData
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
    public static function fromApplicantData(ApplicantData $applicant, DocumentCollection $documents): self
    {
        $info = $applicant->info;
        $document = $info->rawData['idDocs'][0];

        return new self(
            user_xid: $applicant->externalUserId,
            email: $info?->email,
            user_name: $info->getFullName(),
            individual: $info ? PortalIndividualData::fromApplicantInfo($info) : null,
            address: $info?->address ? PortalAddressData::fromAddressData($info->address) : null,
            document: $document ? PortalDocumentData::make(DocumentData::fromArray($document), $documents) : null,
        );
    }

    /**
     * Set portal data email
     */
    public function setEmail(string $email)
    {
        $this->email = $email;
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

