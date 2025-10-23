<?php

declare(strict_types=1);

namespace SumsubSdk\Sumsub\Enums;

/**
 * Webhook event types
 *
 * @see https://docs.sumsub.com/reference/webhooks
 */
enum WebhookType: string
{
    case APPLICANT_CREATED = 'applicantCreated';
    case APPLICANT_PENDING = 'applicantPending';
    case APPLICANT_REVIEWED = 'applicantReviewed';
    case APPLICANT_ON_HOLD = 'applicantOnHold';
    case APPLICANT_PERSONAL_INFO_CHANGED = 'applicantPersonalInfoChanged';
    case APPLICANT_RESET = 'applicantReset';
    case APPLICANT_ACTION_PENDING = 'applicantActionPending';
    case APPLICANT_ACTION_REVIEWED = 'applicantActionReviewed';
    case APPLICANT_ACTION_ON_HOLD = 'applicantActionOnHold';
    case APPLICANT_WORKFLOW_COMPLETED = 'applicantWorkflowCompleted';
    case VIDEO_IDENT_STATUS_CHANGED = 'videoIdentStatusChanged';

    public function isReview(): bool
    {
        return in_array($this, [
            self::APPLICANT_REVIEWED,
            self::APPLICANT_ACTION_REVIEWED
        ]);
    }

    public function isPending(): bool
    {
        return in_array($this, [
            self::APPLICANT_PENDING,
            self::APPLICANT_ACTION_PENDING
        ]);
    }

    public function isCompleted(): bool
    {
        return $this === self::APPLICANT_WORKFLOW_COMPLETED;
    }
}

