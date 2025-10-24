<?php

declare(strict_types=1);

namespace SumsubSdk\Sumsub\Tests\Unit\Resources;

use PHPUnit\Framework\TestCase;
use SumsubSdk\Sumsub\Resources\DocumentCollection;
use SumsubSdk\Sumsub\Resources\DocumentResource;
use SumsubSdk\Sumsub\DataObjects\DocumentData;
use SumsubSdk\Sumsub\Enums\ReviewAnswer;

class DocumentCollectionTest extends TestCase
{
    private function createDocumentResource(string $imageId, ReviewAnswer $answer, string $type = 'IDENTITY'): DocumentResource
    {
        $data = new DocumentData(
            number: null,
            validUntil: null,
            idDocType: 'PASSPORT',
            imageId: $imageId,
            docSetType: $type,
            country: 'USA',
            reviewAnswer: $answer,
            attemptId: null,
            rawData: []
        );

        return new DocumentResource($data);
    }

    public function test_make_creates_collection_from_array()
    {
        $documents = [
            $this->createDocumentResource('img-1', ReviewAnswer::GREEN),
            $this->createDocumentResource('img-2', ReviewAnswer::RED),
        ];

        $collection = DocumentCollection::make($documents);

        $this->assertInstanceOf(DocumentCollection::class, $collection);
        $this->assertEquals(2, $collection->count());
    }

    public function test_get_approved_filters_correctly()
    {
        $documents = [
            $this->createDocumentResource('img-1', ReviewAnswer::GREEN),
            $this->createDocumentResource('img-2', ReviewAnswer::RED),
            $this->createDocumentResource('img-3', ReviewAnswer::GREEN),
            $this->createDocumentResource('img-4', ReviewAnswer::YELLOW),
        ];

        $collection = DocumentCollection::make($documents);
        $approved = $collection->getApproved();

        $this->assertEquals(2, $approved->count());
    }

    public function test_get_rejected_filters_correctly()
    {
        $documents = [
            $this->createDocumentResource('img-1', ReviewAnswer::GREEN),
            $this->createDocumentResource('img-2', ReviewAnswer::RED),
            $this->createDocumentResource('img-3', ReviewAnswer::RED),
        ];

        $collection = DocumentCollection::make($documents);
        $rejected = $collection->getRejected();

        $this->assertEquals(2, $rejected->count());
    }

    public function test_get_by_type_filters_correctly()
    {
        $documents = [
            $this->createDocumentResource('img-1', ReviewAnswer::GREEN, 'IDENTITY'),
            $this->createDocumentResource('img-2', ReviewAnswer::GREEN, 'SELFIE'),
            $this->createDocumentResource('img-3', ReviewAnswer::GREEN, 'IDENTITY'),
        ];

        $collection = DocumentCollection::make($documents);
        $identityDocs = $collection->getByType('IDENTITY');

        $this->assertEquals(2, $identityDocs->count());
    }

    public function test_to_array_returns_array()
    {
        $documents = [
            $this->createDocumentResource('img-1', ReviewAnswer::GREEN),
            $this->createDocumentResource('img-2', ReviewAnswer::RED),
        ];

        $collection = DocumentCollection::make($documents);
        $array = $collection->toArray();

        $this->assertIsArray($array);
        $this->assertCount(2, $array);
        $this->assertArrayHasKey('image_id', $array[0]);
    }

    public function test_all_returns_all_resources()
    {
        $documents = [
            $this->createDocumentResource('img-1', ReviewAnswer::GREEN),
            $this->createDocumentResource('img-2', ReviewAnswer::RED),
        ];

        $collection = DocumentCollection::make($documents);
        $all = $collection->all();

        $this->assertIsArray($all);
        $this->assertCount(2, $all);
        $this->assertInstanceOf(DocumentResource::class, $all[0]);
    }
}

