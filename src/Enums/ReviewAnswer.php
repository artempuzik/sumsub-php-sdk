<?php

declare(strict_types=1);

namespace SumsubSdk\Sumsub\Enums;

/**
 * Review answer (result) values
 *
 * @see https://docs.sumsub.com/reference/applicant-statuses
 */
enum ReviewAnswer: string
{
    case GREEN = 'GREEN';
    case RED = 'RED';
    case YELLOW = 'YELLOW';

    public function isApproved(): bool
    {
        return $this === self::GREEN;
    }

    public function isRejected(): bool
    {
        return $this === self::RED;
    }

    public function requiresReview(): bool
    {
        return $this === self::YELLOW;
    }

    public function getLabel(): string
    {
        return match($this) {
            self::GREEN => 'Approved',
            self::RED => 'Rejected',
            self::YELLOW => 'Requires Review',
        };
    }
}

