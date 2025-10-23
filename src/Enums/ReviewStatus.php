<?php

declare(strict_types=1);

namespace SumsubSdk\Sumsub\Enums;

/**
 * Review status values
 *
 * @see https://docs.sumsub.com/reference/applicant-statuses
 */
enum ReviewStatus: string
{
    case INIT = 'init';
    case PENDING = 'pending';
    case PRECHECKED = 'prechecked';
    case QUEUED = 'queued';
    case COMPLETED = 'completed';
    case ON_HOLD = 'onHold';

    public function isCompleted(): bool
    {
        return $this === self::COMPLETED;
    }

    public function isPending(): bool
    {
        return in_array($this, [self::INIT, self::PENDING, self::PRECHECKED, self::QUEUED]);
    }
}

