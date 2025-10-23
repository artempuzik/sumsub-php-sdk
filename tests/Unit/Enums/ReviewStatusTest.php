<?php

declare(strict_types=1);

namespace SumsubSdk\Sumsub\Tests\Unit\Enums;

use PHPUnit\Framework\TestCase;
use SumsubSdk\Sumsub\Enums\ReviewStatus;

class ReviewStatusTest extends TestCase
{
    public function test_has_correct_values()
    {
        $this->assertEquals('init', ReviewStatus::INIT->value);
        $this->assertEquals('pending', ReviewStatus::PENDING->value);
        $this->assertEquals('completed', ReviewStatus::COMPLETED->value);
        $this->assertEquals('onHold', ReviewStatus::ON_HOLD->value);
    }

    public function test_is_completed()
    {
        $this->assertTrue(ReviewStatus::COMPLETED->isCompleted());
        $this->assertFalse(ReviewStatus::PENDING->isCompleted());
        $this->assertFalse(ReviewStatus::INIT->isCompleted());
    }

    public function test_is_pending()
    {
        $this->assertTrue(ReviewStatus::PENDING->isPending());
        $this->assertTrue(ReviewStatus::INIT->isPending());
        $this->assertFalse(ReviewStatus::COMPLETED->isPending());
    }
}

