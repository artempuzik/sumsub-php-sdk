<?php

declare(strict_types=1);

namespace SumsubSdk\Sumsub\Services;

use SumsubSdk\Sumsub\Client\SumsubClient;
use SumsubSdk\Sumsub\Resources\PortalDataResource;
use SumsubSdk\Sumsub\Exceptions\ApiException;

/**
 * Service for getting Sumsub data in Portal format
 */
class PortalDataService
{
    public function __construct(
        private SumsubClient $client
    ) {}

    /**
     * Get user data in portal format by external user ID
     *
     * @param string $externalUserId User's referral code or external ID
     * @param bool $includeImages Include base64 encoded document images
     * @return array Portal formatted data
     * @throws ApiException
     */
    public function getUserData(string $externalUserId, bool $includeImages = true): array
    {
        $resource = PortalDataResource::fromExternalUserId(
            client: $this->client,
            externalUserId: $externalUserId,
            includeImages: $includeImages
        );

        return $resource->toArray();
    }

    /**
     * Get user data as JSON string
     *
     * @param string $externalUserId User's referral code or external ID
     * @param bool $includeImages Include base64 encoded document images
     * @param bool $prettyPrint Format JSON with indentation
     * @return string JSON string
     * @throws ApiException
     */
    public function getUserDataJson(
        string $externalUserId,
        bool $includeImages = true,
        bool $prettyPrint = false
    ): string {
        $resource = PortalDataResource::fromExternalUserId(
            client: $this->client,
            externalUserId: $externalUserId,
            includeImages: $includeImages
        );

        $options = $prettyPrint ? JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES : 0;

        return $resource->toJson($options);
    }

    /**
     * Get multiple users data
     *
     * @param array $externalUserIds Array of user IDs
     * @param bool $includeImages Include images
     * @return array Array of portal data
     */
    public function getBulkUserData(array $externalUserIds, bool $includeImages = false): array
    {
        $results = [];

        foreach ($externalUserIds as $externalUserId) {
            try {
                $results[$externalUserId] = $this->getUserData($externalUserId, $includeImages);
            } catch (ApiException $e) {
                $results[$externalUserId] = [
                    'error' => $e->getMessage(),
                    'status_code' => $e->getStatusCode(),
                ];
            }
        }

        return $results;
    }
}

