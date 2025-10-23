<?php

declare(strict_types=1);

namespace SumsubSdk\Sumsub\Tests\Feature;

use PHPUnit\Framework\TestCase;
use SumsubSdk\Sumsub\Client\SumsubClient;
use SumsubSdk\Sumsub\Exceptions\ApiException;

/**
 * Integration tests for SumsubClient
 *
 * These tests require actual API credentials
 * Set environment variables:
 * - SUMSUB_APP_TOKEN
 * - SUMSUB_APP_SECRET
 *
 * Run with: vendor/bin/phpunit --group=integration
 *
 * @group integration
 */
class SumsubClientTest extends TestCase
{
    private ?SumsubClient $client = null;

    protected function setUp(): void
    {
        parent::setUp();

        $token = getenv('SUMSUB_APP_TOKEN');
        $secret = getenv('SUMSUB_APP_SECRET');

        if (!$token || !$secret || $token === 'test_token' || $secret === 'test_secret') {
            $this->markTestSkipped('Sumsub credentials not configured. Set SUMSUB_APP_TOKEN and SUMSUB_APP_SECRET environment variables.');
        }

        $this->client = new SumsubClient(
            appToken: $token,
            secretKey: $secret
        );
    }

    public function test_generate_access_token()
    {
        $externalUserId = 'test-user-' . time();
        $levelName = 'basic-kyc-level';

        $tokenData = $this->client->generateAccessToken($externalUserId, $levelName);

        $this->assertIsArray($tokenData);
        $this->assertArrayHasKey('token', $tokenData);
        $this->assertNotEmpty($tokenData['token']);
    }

    public function test_create_applicant()
    {
        $externalUserId = 'test-user-' . time();
        $levelName = 'basic-kyc-level';

        $applicant = $this->client->createApplicant($externalUserId, $levelName);

        $this->assertNotNull($applicant);
        $this->assertEquals($externalUserId, $applicant->getData()->externalUserId);
    }

    public function test_get_applicant_by_external_user_id()
    {
        // First create an applicant
        $externalUserId = 'test-user-' . time();
        $created = $this->client->createApplicant($externalUserId, 'basic-kyc-level');

        // Then retrieve it
        $applicant = $this->client->getApplicantByExternalUserId($externalUserId);

        $this->assertNotNull($applicant);
        $this->assertEquals($externalUserId, $applicant->getData()->externalUserId);
        $this->assertEquals($created->getData()->id, $applicant->getData()->id);
    }

    public function test_get_applicant_status()
    {
        $externalUserId = 'test-user-' . time();
        $created = $this->client->createApplicant($externalUserId, 'basic-kyc-level');

        $status = $this->client->getApplicantStatus($created->getData()->id);

        $this->assertIsArray($status);
    }

    public function test_get_applicant_throws_exception_for_non_existent_user()
    {
        $this->expectException(ApiException::class);

        $this->client->getApplicantByExternalUserId('non-existent-user-' . time());
    }
}

