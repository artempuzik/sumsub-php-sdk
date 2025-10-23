<?php

declare(strict_types=1);

namespace SumsubSdk\Sumsub\Client;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use SumsubSdk\Sumsub\Exceptions\ApiException;
use SumsubSdk\Sumsub\DataObjects\ApplicantData;
use SumsubSdk\Sumsub\DataObjects\DocumentData;
use SumsubSdk\Sumsub\Resources\ApplicantResource;
use SumsubSdk\Sumsub\Resources\DocumentCollection;

/**
 * Main Sumsub API client
 */
class SumsubClient
{
    private const BASE_URL = 'https://api.sumsub.com';

    private Client $httpClient;

    public function __construct(
        private string $appToken,
        private string $secretKey,
        private string $baseUrl = self::BASE_URL
    ) {
        $this->httpClient = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => 30,
        ]);
    }

    /**
     * Get applicant by Sumsub applicant ID
     *
     * @param string $applicantId Sumsub applicant ID
     * @return ApplicantResource
     * @throws ApiException
     */
    public function getApplicant(string $applicantId): ApplicantResource
    {
        $url = "/resources/applicants/{$applicantId}/one";
        $data = $this->request('GET', $url);

        return ApplicantResource::fromArray($data);
    }

    /**
     * Get applicant by your internal external user ID
     *
     * @param string $externalUserId Your internal user ID (e.g., referral_code, user_id)
     * @return ApplicantResource
     * @throws ApiException
     */
    public function getApplicantByExternalUserId(string $externalUserId): ApplicantResource
    {
        $url = "/resources/applicants/-;externalUserId={$externalUserId}/one";
        $data = $this->request('GET', $url);

        return ApplicantResource::fromArray($data);
    }

    /**
     * Create new applicant
     *
     * @param string $externalUserId Your internal user ID
     * @param string $levelName Verification level name
     * @param array|null $info Optional personal information
     * @return ApplicantResource
     * @throws ApiException
     */
    public function createApplicant(
        string $externalUserId,
        string $levelName,
        ?array $info = null
    ): ApplicantResource {
        $url = "/resources/applicants?levelName={$levelName}";
        $body = ['externalUserId' => $externalUserId];

        if ($info) {
            $body['info'] = $info;
        }

        $data = $this->request('POST', $url, $body);

        return ApplicantResource::fromArray($data);
    }

    /**
     * Get applicant verification status with documents info
     *
     * @param string $applicantId Sumsub applicant ID
     * @return array Status data with imageIds
     * @throws ApiException
     */
    public function getApplicantStatus(string $applicantId): array
    {
        $url = "/resources/applicants/{$applicantId}/requiredIdDocsStatus";
        return $this->request('GET', $url);
    }

    /**
     * Get all documents for applicant
     *
     * @param string $applicantId Sumsub applicant ID
     * @return DocumentCollection Collection of documents
     * @throws ApiException
     */
    public function getDocuments(string $applicantId): DocumentCollection
    {
        $statusData = $this->getApplicantStatus($applicantId);
        $documents = [];

        foreach ($statusData as $docSetType => $docSetData) {
            if (isset($docSetData['imageIds']) && is_array($docSetData['imageIds'])) {
                foreach ($docSetData['imageIds'] as $imageId) {
                    $documents[] = DocumentData::fromArray([
                        'imageId' => (string)$imageId,
                        'docSetType' => $docSetType,
                        'idDocType' => $docSetData['idDocType'] ?? null,
                        'country' => $docSetData['country'] ?? null,
                        'reviewResult' => $docSetData['reviewResult'] ?? null,
                        'imageReviewResult' => $docSetData['imageReviewResults'][$imageId] ?? null,
                        'attemptId' => $docSetData['attemptId'] ?? null,
                    ]);
                }
            }
        }

        return DocumentCollection::make($documents);
    }

    /**
     * Get document image as binary data
     *
     * @param string $applicantId Sumsub applicant ID
     * @param string $imageId Document image ID
     * @return string Binary image data (JPEG)
     * @throws ApiException
     */
    public function getDocumentImage(string $applicantId, string $imageId): string
    {
        $url = "/resources/applicants/{$applicantId}/document/{$imageId}";
        return $this->requestRaw('GET', $url);
    }

    /**
     * Generate access token for WebSDK initialization
     *
     * @param string $externalUserId Your internal user ID
     * @param string $levelName Verification level name
     * @return array Token data with 'token' and 'userId' keys
     * @throws ApiException
     */
    public function generateAccessToken(string $externalUserId, string $levelName): array
    {
        $url = "/resources/accessTokens?userId={$externalUserId}&levelName={$levelName}";
        return $this->request('POST', $url);
    }

    /**
     * Upload document for applicant
     *
     * @param string $applicantId Sumsub applicant ID
     * @param string $filePath Path to document file
     * @param array $metadata Document metadata (idDocType, country, etc.)
     * @return string Image ID of uploaded document
     * @throws ApiException
     */
    public function addDocument(
        string $applicantId,
        string $filePath,
        array $metadata
    ): string {
        $url = "/resources/applicants/{$applicantId}/info/idDoc";

        $multipart = [
            [
                'name' => 'metadata',
                'contents' => json_encode($metadata)
            ],
            [
                'name' => 'content',
                'contents' => fopen($filePath, 'r')
            ]
        ];

        $response = $this->requestMultipart('POST', $url, $multipart);

        return $response->getHeader('X-Image-Id')[0] ?? '';
    }

    /**
     * Make HTTP request with JSON response
     *
     * @throws ApiException
     */
    private function request(string $method, string $url, ?array $body = null): array
    {
        $response = $this->sendRequest($method, $url, $body);
        $content = (string)$response->getBody();

        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new ApiException('Invalid JSON response: ' . json_last_error_msg());
        }

        return $data;
    }

    /**
     * Make HTTP request with raw binary response
     *
     * @throws ApiException
     */
    private function requestRaw(string $method, string $url): string
    {
        $response = $this->sendRequest($method, $url);
        return (string)$response->getBody();
    }

    /**
     * Make HTTP request with multipart/form-data
     *
     * @throws ApiException
     */
    private function requestMultipart(string $method, string $url, array $multipart): ResponseInterface
    {
        $timestamp = time();
        $signature = $this->generateSignature($method, $url, $timestamp);

        try {
            return $this->httpClient->request($method, $url, [
                'multipart' => $multipart,
                'headers' => [
                    'X-App-Token' => $this->appToken,
                    'X-App-Access-Sig' => $signature,
                    'X-App-Access-Ts' => $timestamp,
                ],
            ]);
        } catch (GuzzleException $e) {
            throw new ApiException(
                'Request failed: ' . $e->getMessage(),
                $e->getCode()
            );
        }
    }

    /**
     * Send authenticated HTTP request to Sumsub API
     *
     * @throws ApiException
     */
    private function sendRequest(string $method, string $url, ?array $body = null): ResponseInterface
    {
        $timestamp = time();
        $bodyContent = $body ? json_encode($body) : '';
        $signature = $this->generateSignature($method, $url, $timestamp, $bodyContent);

        $options = [
            'headers' => [
                'X-App-Token' => $this->appToken,
                'X-App-Access-Sig' => $signature,
                'X-App-Access-Ts' => $timestamp,
            ],
        ];

        if ($body) {
            $options['json'] = $body;
        }

        try {
            $response = $this->httpClient->request($method, $url, $options);

            if (!in_array($response->getStatusCode(), [200, 201])) {
                $this->handleErrorResponse($response);
            }

            return $response;

        } catch (GuzzleException $e) {
            $this->handleGuzzleException($e);
        }
    }

    /**
     * Generate HMAC SHA-256 signature for Sumsub API authentication
     *
     * @param string $method HTTP method (GET, POST, etc.)
     * @param string $url Request URL path
     * @param int $timestamp Unix timestamp
     * @param string $body Request body (for POST/PUT)
     * @return string HMAC signature
     */
    private function generateSignature(
        string $method,
        string $url,
        int $timestamp,
        string $body = ''
    ): string {
        $data = $timestamp . strtoupper($method) . $url . $body;
        return hash_hmac('sha256', $data, $this->secretKey);
    }

    /**
     * Handle API error response and throw exception
     *
     * @throws ApiException
     */
    private function handleErrorResponse(ResponseInterface $response): void
    {
        $statusCode = $response->getStatusCode();
        $content = (string)$response->getBody();
        $data = json_decode($content, true);

        $message = $data['description'] ?? 'API request failed';
        $correlationId = $data['correlationId'] ?? null;

        throw (new ApiException($message, $statusCode, $data, $correlationId));
    }

    /**
     * Handle Guzzle HTTP client exception
     *
     * @throws ApiException
     */
    private function handleGuzzleException(GuzzleException $e): never
    {
        if (method_exists($e, 'getResponse') && $response = $e->getResponse()) {
            $this->handleErrorResponse($response);
        }

        throw new ApiException(
            'Request failed: ' . $e->getMessage(),
            $e->getCode()
        );
    }
}

