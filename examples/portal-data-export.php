<?php

require_once __DIR__ . '/../vendor/autoload.php';

use SumsubSdk\Sumsub\Client\SumsubClient;
use SumsubSdk\Sumsub\Services\PortalDataService;

// Initialize client
$client = new SumsubClient(
    appToken: getenv('SUMSUB_APP_TOKEN'),
    secretKey: getenv('SUMSUB_APP_SECRET')
);

// Initialize portal data service
$portalService = new PortalDataService($client);

// Example 1: Get user data without images (faster)
try {
    $userData = $portalService->getUserData(
        externalUserId: 'USER123',
        includeImages: false
    );

    echo "User data (without images):\n";
    print_r($userData);

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// Example 2: Get user data with images (slower, larger payload)
try {
    $userData = $portalService->getUserData(
        externalUserId: 'USER123',
        includeImages: true
    );

    echo "\nUser data (with images):\n";
    echo "User XID: " . $userData['user_xid'] . "\n";
    echo "Email: " . $userData['email'] . "\n";
    echo "Has front image: " . (isset($userData['document']['front']) ? 'Yes' : 'No') . "\n";
    echo "Has back image: " . (isset($userData['document']['back']) ? 'Yes' : 'No') . "\n";
    echo "Has face image: " . (isset($userData['document']['face']) ? 'Yes' : 'No') . "\n";

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// Example 3: Get data as JSON
try {
    $json = $portalService->getUserDataJson(
        externalUserId: 'USER123',
        includeImages: false,
        prettyPrint: true
    );

    echo "\nJSON output:\n";
    echo $json;

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// Example 4: Get multiple users data
try {
    $bulkData = $portalService->getBulkUserData(
        externalUserIds: ['USER123', 'USER456', 'USER789'],
        includeImages: false
    );

    echo "\nBulk export:\n";
    foreach ($bulkData as $userId => $data) {
        if (isset($data['error'])) {
            echo "  $userId: Error - {$data['error']}\n";
        } else {
            echo "  $userId: " . ($data['email'] ?? 'No email') . "\n";
        }
    }

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

/**
 * Expected output format:
 *
 * {
 *     "user_xid": "USER123",
 *     "email": "user@example.com",
 *     "user_name": "johndoe",
 *     "individual": {
 *         "first_name": "John",
 *         "last_name": "Doe",
 *         "date_of_birth": "1990-05-15",
 *         "occupation": null,
 *         "annual_income": null
 *     },
 *     "address": {
 *         "country": "US",
 *         "city": "New York",
 *         "post_code": "10001",
 *         "details": "123 Main Street, Building 5, Apt 4B"
 *     },
 *     "document": {
 *         "type": "2",
 *         "number": null,
 *         "country": "US",
 *         "expiry_date": null,
 *         "front": "data:image/jpeg;base64,/9j/4AAQSkZJRg...",
 *         "back": "data:image/jpeg;base64,/9j/4AAQSkZJRg...",
 *         "face": "data:image/jpeg;base64,/9j/4AAQSkZJRg..."
 *     }
 * }
 */

