<?php

require __DIR__ . '/../vendor/autoload.php';

use SumsubSdk\Sumsub\Client\SumsubClient;
use SumsubSdk\Sumsub\Exceptions\ApiException;

// Initialize client
$client = new SumsubClient(
    appToken: getenv('SUMSUB_APP_TOKEN'),
    secretKey: getenv('SUMSUB_APP_SECRET')
);

try {
    // Get applicant by external user ID
    $applicant = $client->getApplicantByExternalUserId('user-123');

    // Get structured data
    $data = $applicant->toArray();

    echo "Verification Status: {$data['verification_status']}\n";
    echo "Is Verified: " . ($data['is_verified'] ? 'Yes' : 'No') . "\n";

    if ($data['personal_info']) {
        echo "Name: {$data['personal_info']['full_name']}\n";
        echo "Country: {$data['personal_info']['country']}\n";
    }

    // Access raw data object
    $applicantData = $applicant->getData();

    if ($applicantData->isVerified()) {
        echo "✅ User is verified!\n";
    } elseif ($applicantData->isRejected()) {
        echo "❌ User was rejected\n";
    } else {
        echo "⏳ Verification pending\n";
    }

    // Get documents
    $documents = $client->getDocuments($applicantData->id);

    echo "\nTotal documents: {$documents->count()}\n";
    echo "Approved documents: {$documents->getApproved()->count()}\n";

    foreach ($documents->toArray() as $doc) {
        echo "- {$doc['type']}: {$doc['review_status']}\n";
    }

} catch (ApiException $e) {
    echo "Error: {$e->getMessage()}\n";
    echo "Status: {$e->getStatusCode()}\n";
    if ($correlationId = $e->getCorrelationId()) {
        echo "Correlation ID: {$correlationId}\n";
    }
}

