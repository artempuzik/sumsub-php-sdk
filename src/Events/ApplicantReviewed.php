<?php

declare(strict_types=1);

namespace SumsubSdk\Sumsub\Events;

/**
 * Fired when applicant verification is reviewed
 */
class ApplicantReviewed extends Event
{
    /**
     * Check if applicant was approved
     */
    public function isApproved(): bool
    {
        return $this->webhook->isApproved();
    }

    /**
     * Check if applicant was rejected
     */
    public function isRejected(): bool
    {
        return $this->webhook->isRejected();
    }

    /**
     * Check if requires additional review
     */
    public function requiresReview(): bool
    {
        return $this->webhook->requiresReview();
    }
}

