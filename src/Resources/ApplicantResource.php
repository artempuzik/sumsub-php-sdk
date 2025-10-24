<?php

declare(strict_types=1);

namespace SumsubSdk\Sumsub\Resources;

use SumsubSdk\Sumsub\DataObjects\ApplicantData;
use SumsubSdk\Sumsub\DataObjects\AddressData;
use SumsubSdk\Sumsub\DataObjects\ApplicantInfoData;
use SumsubSdk\Sumsub\DataObjects\DocumentData;
/**
 * Resource for transforming applicant data
 */
class ApplicantResource
{
    public function __construct(
        private ApplicantData $applicant
    ) {}

    public static function make(ApplicantData $applicant): self
    {
        return new self($applicant);
    }

    public static function fromArray(array $data): self
    {
        return new self(ApplicantData::fromArray($data));
    }

    public function toArray(): array
    {
        return [
            'id' => $this->applicant->id,
            'external_user_id' => $this->applicant->externalUserId,
            'created_at' => $this->applicant->createdAt,
            'inspection_id' => $this->applicant->inspectionId,
            'type' => $this->applicant->type?->value,
            'lang' => $this->applicant->lang,
            'key' => $this->applicant->key,
            'client_id' => $this->applicant->clientId,
            'applicant_platform' => $this->applicant->applicantPlatform,
            'email' => $this->applicant->email,
            'verification_status' => $this->getVerificationStatus(),
            'is_verified' => $this->applicant->isVerified(),
            'is_rejected' => $this->applicant->isRejected(),
            'is_pending' => $this->applicant->isPending(),
            'personal_info' => $this->getPersonalInfo(),
            'fixed_info' => $this->getFixedInfo(),
            'review' => $this->getReviewInfo(),
            'agreement' => $this->applicant->agreement,
            'required_id_docs' => $this->applicant->requiredIdDocs,
        ];
    }

    public function address(AddressData $address): array
    {
        if (!$address) {
            return [];
        }

        return [
            'details' => $address->getFormatted(),
            'country' => $address->country,
            'city' => $address->town,
            'post_code' => $address->postCode,
        ];
    }

    public function individual(ApplicantInfoData $info): array
    {
        return [
            'first_name' => $info->firstName,
            'last_name' => $info->lastName,
            'date_of_birth' => $info->dob,
            'country' => $info->country,
            'nationality' => $info->nationality,
            'phone' => $info->phone,
            'email' => $info->email,
        ];
    }

    public function getApplicantPersonalInfo(): array
    {
        if (!$this->applicant->info) {
            return [];
        }

        $info = $this->applicant->info;
        $data = [
            'email' => $info->email,
            'user_name' => $info->getFullName(),
            'individual' => $this->individual($info),
            'address' => $this->address($info->address),
            'document' => $this->getDocumentInfo($info->rawData['idDocs'][0]),
        ];

        return $data;
    }

    /**
     * Get verification status string
     */
    private function getVerificationStatus(): string
    {
        if ($this->applicant->isVerified()) {
            return 'verified';
        }

        if ($this->applicant->isRejected()) {
            return 'rejected';
        }

        return 'pending';
    }

    /**
     * Get formatted personal info array
     */
    private function getPersonalInfo(): ?array
    {
        if (!$this->applicant->info) {
            return null;
        }

        $info = $this->applicant->info;

        return [
            'full_name' => $info->getFullName(),
            'first_name' => $info->firstName,
            'last_name' => $info->lastName,
            'middle_name' => $info->middleName,
            'date_of_birth' => $info->dob,
            'country' => $info->country,
            'nationality' => $info->nationality,
            'phone' => $info->phone,
            'email' => $info->email,
            'address' => $info->address ? [
                'formatted' => $info->address->getFormatted(),
                'country' => $info->address->country,
                'city' => $info->address->town,
                'street' => $info->address->street,
                'post_code' => $info->address->postCode,
            ] : null,
        ];
    }

    /**
     * Get formatted review info array
     */
    private function getReviewInfo(): ?array
    {
        if (!$this->applicant->review) {
            return null;
        }

        $review = $this->applicant->review;

        return [
            'review_id' => $review->reviewId,
            'attempt_id' => $review->attemptId,
            'attempt_count' => $review->attemptCnt,
            'level_name' => $review->levelName,
            'status' => $review->reviewStatus?->value,
            'created_at' => $review->createDate,
            'reviewed_at' => $review->reviewDate,
            'result' => $review->reviewResult ? [
                'answer' => $review->reviewResult->reviewAnswer?->value,
                'label' => $review->reviewResult->reviewAnswer?->getLabel(),
                'moderation_comment' => $review->reviewResult->moderationComment,
                'reject_type' => $review->reviewResult->reviewRejectType,
                'reject_labels' => $review->reviewResult->rejectLabels,
            ] : null,
        ];
    }

    /**
     * Get formatted fixed info array
     */
    private function getFixedInfo(): ?array
    {
        if (!$this->applicant->fixedInfo) {
            return null;
        }

        $info = $this->applicant->fixedInfo;

        return [
            'full_name' => $info->getFullName(),
            'first_name' => $info->firstName,
            'last_name' => $info->lastName,
            'middle_name' => $info->middleName,
        ];
    }

    /**
     * Get underlying data object
     */
    public function getData(): ApplicantData
    {
        return $this->applicant;
    }

    /**
     * Get formatted document info array
     */
    private function getDocumentInfo(array $document): ?array
    {
        if (!$document) {
            return [];
        }

        $doc = DocumentData::fromArray($document);

        return [
            'type' => $doc->getTypeNumber(),
            'number' => $doc->number,
            'country' => $doc->country,
            'expiry_date' => $doc->validUntil,
            'doc_type' => $doc->idDocType,
            'doc' => $doc
        ];
    }
}

