<?php

declare(strict_types=1);

namespace SumsubSdk\Sumsub\Tests\Feature;

use PHPUnit\Framework\TestCase;
use SumsubSdk\Sumsub\Webhooks\WebhookHandler;
use SumsubSdk\Sumsub\Events\ApplicantReviewed;
use SumsubSdk\Sumsub\Exceptions\ValidationException;

class WebhookHandlerTest extends TestCase
{
    private WebhookHandler $handler;
    private string $secretKey = 'test_secret_key';

    protected function setUp(): void
    {
        parent::setUp();
        $this->handler = new WebhookHandler($this->secretKey);
    }

    public function test_handle_valid_webhook()
    {
        $payload = json_encode([
            'type' => 'applicantReviewed',
            'applicantId' => 'test-applicant-id',
            'externalUserId' => 'user-123',
            'reviewStatus' => 'completed',
            'reviewResult' => [
                'reviewAnswer' => 'GREEN'
            ]
        ]);

        $signature = hash_hmac('sha256', $payload, $this->secretKey);

        $webhook = $this->handler->handle($payload, $signature);

        $this->assertEquals('test-applicant-id', $webhook->applicantId);
        $this->assertEquals('user-123', $webhook->externalUserId);
        $this->assertTrue($webhook->isApproved());
    }

    public function test_handle_throws_exception_for_invalid_signature()
    {
        $this->expectException(ValidationException::class);

        $payload = json_encode([
            'type' => 'applicantReviewed',
            'applicantId' => 'test-id',
            'externalUserId' => 'user-123',
        ]);

        $wrongSignature = 'wrong_signature';

        $this->handler->handle($payload, $wrongSignature);
    }

    public function test_handle_from_request()
    {
        $payload = json_encode([
            'type' => 'applicantReviewed',
            'applicantId' => 'test-id',
            'externalUserId' => 'user-123',
        ]);

        $signature = hash_hmac('sha256', $payload, $this->secretKey);

        $headers = [
            'x-payload-digest' => [$signature],
            'x-payload-digest-alg' => ['sha256'],
        ];

        $webhook = $this->handler->handleFromRequest($headers, $payload);

        $this->assertEquals('test-id', $webhook->applicantId);
        $this->assertEquals('user-123', $webhook->externalUserId);
    }

    public function test_events_are_dispatched()
    {
        $eventDispatched = false;

        $this->handler->getDispatcher()->listen(
            ApplicantReviewed::class,
            function() use (&$eventDispatched) {
                $eventDispatched = true;
            }
        );

        $payload = json_encode([
            'type' => 'applicantReviewed',
            'applicantId' => 'test-id',
            'externalUserId' => 'user-123',
        ]);

        $signature = hash_hmac('sha256', $payload, $this->secretKey);

        $this->handler->handle($payload, $signature);

        $this->assertTrue($eventDispatched);
    }
}

