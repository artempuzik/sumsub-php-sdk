<?php

declare(strict_types=1);

namespace SumsubSdk\Sumsub\Tests\Unit\DataObjects;

use PHPUnit\Framework\TestCase;
use SumsubSdk\Sumsub\DataObjects\DocumentData;
use SumsubSdk\Sumsub\Enums\ReviewAnswer;

class DocumentDataTest extends TestCase
{
    public function test_from_array_creates_instance()
    {
        $data = [
            'imageId' => 'img-123',
            'docSetType' => 'IDENTITY',
            'idDocType' => 'PASSPORT',
            'country' => 'USA',
        ];

        $document = DocumentData::fromArray($data);

        $this->assertInstanceOf(DocumentData::class, $document);
        $this->assertEquals('img-123', $document->imageId);
        $this->assertEquals('IDENTITY', $document->docSetType);
        $this->assertEquals('PASSPORT', $document->idDocType);
        $this->assertEquals('USA', $document->country);
    }

    public function test_is_approved_returns_correct_value()
    {
        $approved = new DocumentData(
            number: null,
            validUntil: null,
            idDocType: 'PASSPORT',
            imageId: 'img-1',
            docSetType: 'IDENTITY',
            country: 'USA',
            reviewAnswer: ReviewAnswer::GREEN,
            attemptId: null,
            rawData: []
        );

        $rejected = new DocumentData(
            number: null,
            validUntil: null,
            idDocType: 'PASSPORT',
            imageId: 'img-2',
            docSetType: 'IDENTITY',
            country: 'USA',
            reviewAnswer: ReviewAnswer::RED,
            attemptId: null,
            rawData: []
        );

        $this->assertTrue($approved->isApproved());
        $this->assertFalse($rejected->isApproved());
    }

    public function test_is_rejected_returns_correct_value()
    {
        $rejected = new DocumentData(
            number: null,
            validUntil: null,
            idDocType: 'PASSPORT',
            imageId: 'img-1',
            docSetType: 'IDENTITY',
            country: 'USA',
            reviewAnswer: ReviewAnswer::RED,
            attemptId: null,
            rawData: []
        );

        $this->assertTrue($rejected->isRejected());
    }

    public function test_requires_review_returns_correct_value()
    {
        $yellow = new DocumentData(
            number: null,
            validUntil: null,
            idDocType: 'PASSPORT',
            imageId: 'img-1',
            docSetType: 'IDENTITY',
            country: 'USA',
            reviewAnswer: ReviewAnswer::YELLOW,
            attemptId: null,
            rawData: []
        );

        $this->assertTrue($yellow->requiresReview());
    }
}

