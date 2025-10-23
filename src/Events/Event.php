<?php

declare(strict_types=1);

namespace SumsubSdk\Sumsub\Events;

use SumsubSdk\Sumsub\Webhooks\WebhookData;

/**
 * Base event class
 */
abstract class Event
{
    public function __construct(
        public readonly WebhookData $webhook
    ) {}

    /**
     * Get Sumsub applicant ID
     */
    public function getApplicantId(): string
    {
        return $this->webhook->applicantId;
    }

    /**
     * Get external user ID (your internal ID)
     */
    public function getExternalUserId(): string
    {
        return $this->webhook->externalUserId;
    }

    /**
     * Get full webhook data
     */
    public function getWebhookData(): WebhookData
    {
        return $this->webhook;
    }
}

