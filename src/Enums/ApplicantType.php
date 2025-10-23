<?php

declare(strict_types=1);

namespace SumsubSdk\Sumsub\Enums;

/**
 * Applicant type
 */
enum ApplicantType: string
{
    case INDIVIDUAL = 'individual';
    case COMPANY = 'company';

    public function isIndividual(): bool
    {
        return $this === self::INDIVIDUAL;
    }

    public function isCompany(): bool
    {
        return $this === self::COMPANY;
    }
}

