<?php

declare(strict_types=1);

namespace SumsubSdk\Sumsub\Tests\Unit\Enums;

use PHPUnit\Framework\TestCase;
use SumsubSdk\Sumsub\Enums\ReviewAnswer;

class ReviewAnswerTest extends TestCase
{
    public function test_has_correct_values()
    {
        $this->assertEquals('GREEN', ReviewAnswer::GREEN->value);
        $this->assertEquals('RED', ReviewAnswer::RED->value);
        $this->assertEquals('YELLOW', ReviewAnswer::YELLOW->value);
    }

    public function test_is_approved()
    {
        $this->assertTrue(ReviewAnswer::GREEN->isApproved());
        $this->assertFalse(ReviewAnswer::RED->isApproved());
        $this->assertFalse(ReviewAnswer::YELLOW->isApproved());
    }

    public function test_is_rejected()
    {
        $this->assertFalse(ReviewAnswer::GREEN->isRejected());
        $this->assertTrue(ReviewAnswer::RED->isRejected());
        $this->assertFalse(ReviewAnswer::YELLOW->isRejected());
    }

    public function test_requires_review()
    {
        $this->assertFalse(ReviewAnswer::GREEN->requiresReview());
        $this->assertFalse(ReviewAnswer::RED->requiresReview());
        $this->assertTrue(ReviewAnswer::YELLOW->requiresReview());
    }

    public function test_get_label()
    {
        $this->assertEquals('Approved', ReviewAnswer::GREEN->getLabel());
        $this->assertEquals('Rejected', ReviewAnswer::RED->getLabel());
        $this->assertEquals('Requires Review', ReviewAnswer::YELLOW->getLabel());
    }
}

