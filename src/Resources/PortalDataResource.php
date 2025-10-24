<?php

declare(strict_types=1);

namespace SumsubSdk\Sumsub\Resources;

use SumsubSdk\Sumsub\Client\SumsubClient;
use SumsubSdk\Sumsub\DataObjects\PortalUserData;
use SumsubSdk\Sumsub\DataObjects\PortalDocumentData;

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
     * @param SumsubClient $client Sumsub client instance
     * @param string $externalUserId User's referral code or external ID
     * @param bool $includeImages Whether to include base64 encoded images
     * @return self
     */
    public static function fromExternalUserId(
        SumsubClient $client,
        string $externalUserId,
        bool $includeImages = true
    ): self {
        // Get applicant data
        $applicant = $client->getApplicantByExternalUserId($externalUserId);
        $applicantData = $applicant->getData();

        // Create base portal data
        $portalData = PortalUserData::fromApplicantData($applicantData);

        // Get documents if images are needed
        if ($includeImages) {
            try {
                $documents = $client->getDocuments($applicantData->id);

                // Find identity document
                $identityDocs = $documents->getByType('IDENTITY');
                $selfieDocs = $documents->getByType('SELFIE');

                $frontImage = null;
                $backImage = null;
                $faceImage = null;

                // Get identity document images using base64 method
                if ($identityDocs->count() > 0) {
                    $identityImages = $identityDocs->all();

                    if (isset($identityImages[0])) {
                        try {
                            $imageId = $identityImages[0]->getData()->imageId;
                            $frontImage = $client->getDocumentImageBase64($applicantData->inspectionId, $imageId, true);
                        } catch (\Exception $e) {
                            // Skip if image retrieval fails
                        }
                    }

                    if (isset($identityImages[1])) {
                        try {
                            $imageId = $identityImages[1]->getData()->imageId;
                            $backImage = $client->getDocumentImageBase64($applicantData->inspectionId, $imageId, true);
                        } catch (\Exception $e) {
                            // Skip if image retrieval fails
                        }
                    }
                }

                // Get selfie image using base64 method
                if ($selfieDocs->count() > 0) {
                    $selfieImages = $selfieDocs->all();

                    if (isset($selfieImages[0])) {
                        try {
                            $imageId = $selfieImages[0]->getData()->imageId;
                            $faceImage = $client->getDocumentImageBase64($applicantData->inspectionId, $imageId, true);
                        } catch (\Exception $e) {
                            // Skip if image retrieval fails
                        }
                    }
                }

                // Get document metadata
                $firstIdentityDoc = $identityDocs->all()[0] ?? null;
                $docData = $firstIdentityDoc?->getData();

                // Create portal document
                $document = PortalDocumentData::fromDocumentImages(
                    docType: $docData?->idDocType ?? $docData?->docSetType ?? 'IDENTITY',
                    docNumber: null, // Not available in imageIds
                    country: $docData?->country,
                    expiryDate: null, // Not available in imageIds
                    frontImage: $frontImage,
                    backImage: $backImage,
                    faceImage: $faceImage
                );

                // Update portal data with document
                $portalData = new PortalUserData(
                    user_xid: $portalData->user_xid,
                    email: $portalData->email,
                    user_name: $portalData->user_name,
                    individual: $portalData->individual,
                    address: $portalData->address,
                    document: $document,
                );

            } catch (\Exception $e) {
                // Documents not available, return without images
            }
        }

        return new self($portalData);
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
}
