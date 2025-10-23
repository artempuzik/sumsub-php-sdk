<?php

declare(strict_types=1);

namespace SumsubSdk\Sumsub\Tests\Unit\DataObjects;

use PHPUnit\Framework\TestCase;
use SumsubSdk\Sumsub\DataObjects\ApplicantData;
use SumsubSdk\Sumsub\DataObjects\ReviewData;
use SumsubSdk\Sumsub\DataObjects\ReviewResultData;
use SumsubSdk\Sumsub\Enums\ReviewAnswer;
use SumsubSdk\Sumsub\Enums\ReviewStatus;

class ApplicantDataTest extends TestCase
{
    public function test_from_array_creates_instance()
    {
        $data = [
            'id' => 'test-id',
            'externalUserId' => 'user-123',
            'createdAt' => '2024-01-01 10:00:00',
        ];

        $applicant = ApplicantData::fromArray($data);

        $this->assertInstanceOf(ApplicantData::class, $applicant);
        $this->assertEquals('test-id', $applicant->id);
        $this->assertEquals('user-123', $applicant->externalUserId);
        $this->assertEquals('2024-01-01 10:00:00', $applicant->createdAt);
    }

    public function test_is_verified_returns_true_when_approved()
    {
        $reviewResult = new ReviewResultData(
            reviewAnswer: ReviewAnswer::GREEN,
            moderationComment: null,
            clientComment: null,
            reviewRejectType: null,
            rejectLabels: null,
            rawData: []
        );

        $review = new ReviewData(
            reviewId: 'review-1',
            attemptId: 'attempt-1',
            attemptCnt: 1,
            levelName: 'basic-kyc-level',
            createDate: '2024-01-01',
            reviewDate: '2024-01-02',
            reviewStatus: ReviewStatus::COMPLETED,
            reviewResult: $reviewResult,
            priority: 0,
            rawData: []
        );

        $applicant = new ApplicantData(
            id: 'test-id',
            externalUserId: 'user-123',
            createdAt: '2024-01-01',
            inspectionId: null,
            type: null,
            lang: null,
            info: null,
            review: $review,
            requiredIdDocs: null,
            rawData: []
        );

        $this->assertTrue($applicant->isVerified());
        $this->assertFalse($applicant->isRejected());
        $this->assertFalse($applicant->isPending());
    }

    public function test_is_rejected_returns_true_when_rejected()
    {
        $reviewResult = new ReviewResultData(
            reviewAnswer: ReviewAnswer::RED,
            moderationComment: null,
            clientComment: null,
            reviewRejectType: null,
            rejectLabels: null,
            rawData: []
        );

        $review = new ReviewData(
            reviewId: 'review-1',
            attemptId: 'attempt-1',
            attemptCnt: 1,
            levelName: 'basic-kyc-level',
            createDate: '2024-01-01',
            reviewDate: '2024-01-02',
            reviewStatus: ReviewStatus::COMPLETED,
            reviewResult: $reviewResult,
            priority: 0,
            rawData: []
        );

        $applicant = new ApplicantData(
            id: 'test-id',
            externalUserId: 'user-123',
            createdAt: '2024-01-01',
            inspectionId: null,
            type: null,
            lang: null,
            info: null,
            review: $review,
            requiredIdDocs: null,
            rawData: []
        );

        $this->assertFalse($applicant->isVerified());
        $this->assertTrue($applicant->isRejected());
        $this->assertFalse($applicant->isPending());
    }

    public function test_is_pending_returns_true_when_no_review()
    {
        $applicant = new ApplicantData(
            id: 'test-id',
            externalUserId: 'user-123',
            createdAt: '2024-01-01',
            inspectionId: null,
            type: null,
            lang: null,
            info: null,
            review: null,
            requiredIdDocs: null,
            rawData: []
        );

        $this->assertFalse($applicant->isVerified());
        $this->assertFalse($applicant->isRejected());
        $this->assertTrue($applicant->isPending());
    }
}

