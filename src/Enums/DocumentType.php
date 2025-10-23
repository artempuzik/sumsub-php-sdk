<?php

declare(strict_types=1);

namespace SumsubSdk\Sumsub\Enums;

/**
 * Document types supported by Sumsub
 *
 * @see https://docs.sumsub.com/reference/document-types
 */
enum DocumentType: string
{
    case IDENTITY = 'IDENTITY';
    case SELFIE = 'SELFIE';
    case VIDEO_SELFIE = 'VIDEO_SELFIE';
    case PROOF_OF_RESIDENCE = 'PROOF_OF_RESIDENCE';
    case PAYMENT_METHOD = 'PAYMENT_METHOD';
    case COMPANY_DOC = 'COMPANY_DOC';
    case ADDITIONAL_DOCUMENT = 'ADDITIONAL_DOCUMENT';

    // Identity document subtypes
    case ID_CARD = 'ID_CARD';
    case PASSPORT = 'PASSPORT';
    case DRIVERS = 'DRIVERS';
    case RESIDENCE_PERMIT = 'RESIDENCE_PERMIT';
    case VISA = 'VISA';
    case HEALTH_ID = 'HEALTH_ID';

    public function isIdentityDocument(): bool
    {
        return in_array($this, [
            self::ID_CARD,
            self::PASSPORT,
            self::DRIVERS,
            self::RESIDENCE_PERMIT,
            self::VISA,
            self::HEALTH_ID
        ]);
    }

    public function requiresMultipleSides(): bool
    {
        return in_array($this, [
            self::ID_CARD,
            self::DRIVERS,
            self::RESIDENCE_PERMIT
        ]);
    }
}

