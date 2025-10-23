<?php

declare(strict_types=1);

namespace SumsubSdk\Sumsub\Tests\Unit\Events;

use PHPUnit\Framework\TestCase;
use SumsubSdk\Sumsub\Events\EventDispatcher;
use SumsubSdk\Sumsub\Events\ApplicantReviewed;
use SumsubSdk\Sumsub\Webhooks\WebhookData;
use SumsubSdk\Sumsub\Enums\WebhookType;

class EventDispatcherTest extends TestCase
{
    private EventDispatcher $dispatcher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dispatcher = new EventDispatcher();
    }

    public function test_listen_registers_listener()
    {
        $called = false;

        $this->dispatcher->listen(ApplicantReviewed::class, function() use (&$called) {
            $called = true;
        });

        $this->assertTrue($this->dispatcher->hasListeners(ApplicantReviewed::class));
    }

    public function test_dispatch_calls_listeners()
    {
        $called = false;

        $this->dispatcher->listen(ApplicantReviewed::class, function($event) use (&$called) {
            $called = true;
        });

        $webhookData = new WebhookData(
            type: WebhookType::APPLICANT_REVIEWED,
            applicantId: 'test-id',
            externalUserId: 'user-123',
            inspectionId: null,
            correlationId: null,
            reviewStatus: null,
            reviewAnswer: null,
            applicantType: null,
            createdAt: null,
            reviewResult: null,
            rawPayload: []
        );

        $event = new ApplicantReviewed($webhookData);
        $this->dispatcher->dispatch($event);

        $this->assertTrue($called);
    }

    public function test_dispatch_calls_multiple_listeners()
    {
        $counter = 0;

        $this->dispatcher->listen(ApplicantReviewed::class, function() use (&$counter) {
            $counter++;
        });

        $this->dispatcher->listen(ApplicantReviewed::class, function() use (&$counter) {
            $counter++;
        });

        $webhookData = new WebhookData(
            type: WebhookType::APPLICANT_REVIEWED,
            applicantId: 'test-id',
            externalUserId: 'user-123',
            inspectionId: null,
            correlationId: null,
            reviewStatus: null,
            reviewAnswer: null,
            applicantType: null,
            createdAt: null,
            reviewResult: null,
            rawPayload: []
        );

        $event = new ApplicantReviewed($webhookData);
        $this->dispatcher->dispatch($event);

        $this->assertEquals(2, $counter);
    }

    public function test_forget_removes_listeners()
    {
        $this->dispatcher->listen(ApplicantReviewed::class, function() {});

        $this->assertTrue($this->dispatcher->hasListeners(ApplicantReviewed::class));

        $this->dispatcher->forget(ApplicantReviewed::class);

        $this->assertFalse($this->dispatcher->hasListeners(ApplicantReviewed::class));
    }

    public function test_forget_all_removes_all_listeners()
    {
        $this->dispatcher->listen(ApplicantReviewed::class, function() {});

        $this->dispatcher->forgetAll();

        $this->assertFalse($this->dispatcher->hasListeners(ApplicantReviewed::class));
    }

    public function test_get_listeners_returns_array()
    {
        $callback1 = function() {};
        $callback2 = function() {};

        $this->dispatcher->listen(ApplicantReviewed::class, $callback1);
        $this->dispatcher->listen(ApplicantReviewed::class, $callback2);

        $listeners = $this->dispatcher->getListeners(ApplicantReviewed::class);

        $this->assertIsArray($listeners);
        $this->assertCount(2, $listeners);
    }
}

