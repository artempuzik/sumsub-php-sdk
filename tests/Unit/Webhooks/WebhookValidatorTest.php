<?php

declare(strict_types=1);

namespace SumsubSdk\Sumsub\Tests\Unit\Webhooks;

use PHPUnit\Framework\TestCase;
use SumsubSdk\Sumsub\Validators\WebhookValidator;
use SumsubSdk\Sumsub\Exceptions\ValidationException;

class WebhookValidatorTest extends TestCase
{
    private WebhookValidator $validator;
    private string $secretKey = 'test_secret_key';

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new WebhookValidator($this->secretKey);
    }

    public function test_validate_signature_with_correct_signature()
    {
        $payload = '{"type":"applicantReviewed","applicantId":"test-id"}';
        $signature = hash_hmac('sha256', $payload, $this->secretKey);

        $result = $this->validator->validateSignature($payload, $signature);

        $this->assertTrue($result);
    }

    public function test_validate_signature_throws_exception_with_incorrect_signature()
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Invalid webhook signature');

        $payload = '{"type":"applicantReviewed","applicantId":"test-id"}';
        $wrongSignature = 'wrong_signature';

        $this->validator->validateSignature($payload, $wrongSignature);
    }

    public function test_validate_payload_with_valid_data()
    {
        $payload = [
            'type' => 'applicantReviewed',
            'applicantId' => 'test-id',
            'externalUserId' => 'user-123',
        ];

        $result = $this->validator->validatePayload($payload);

        $this->assertTrue($result);
    }

    public function test_validate_payload_throws_exception_when_type_missing()
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Missing required field: type');

        $payload = [
            'applicantId' => 'test-id',
        ];

        $this->validator->validatePayload($payload);
    }

    public function test_validate_payload_throws_exception_when_applicant_id_missing()
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Missing required field: applicantId');

        $payload = [
            'type' => 'applicantReviewed',
        ];

        $this->validator->validatePayload($payload);
    }

    public function test_validate_signature_with_sha1_algorithm()
    {
        $payload = '{"type":"applicantReviewed"}';
        $signature = hash_hmac('sha1', $payload, $this->secretKey);

        $result = $this->validator->validateSignature($payload, $signature, 'sha1');

        $this->assertTrue($result);
    }
}

