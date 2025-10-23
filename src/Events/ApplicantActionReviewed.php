<?php

declare(strict_types=1);

namespace SumsubSdk\Sumsub\Events;

/**
 * Fired when applicant action is reviewed
 */
class ApplicantActionReviewed extends Event
{
    /**
     * Check if action was approved
     */
    public function isApproved(): bool
    {
        return $this->webhook->isApproved();
    }

    /**
     * Check if action was rejected
     */
    public function isRejected(): bool
    {
        return $this->webhook->isRejected();
    }
}

