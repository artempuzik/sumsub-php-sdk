# Sumsub PHP SDK

Complete PHP SDK for Sumsub KYC/AML verification service with full support for DTOs, Resources, Enums, Events and Webhook handling.

[![PHP Version](https://img.shields.io/badge/php-%5E8.1-blue)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)

## Features

✅ **Type-safe** - Full PHP 8.1+ type hints with enums and readonly properties
✅ **DTOs** - Structured Data Transfer Objects instead of arrays
✅ **Resources** - Clean data transformation with `toArray()` methods
✅ **Events** - Event-driven webhook handling with custom listeners
✅ **Enums** - Type-safe constants for statuses, types, and answers
✅ **Validation** - Automatic webhook signature validation
✅ **Exceptions** - Detailed error handling with correlation IDs
✅ **Laravel Ready** - Easy integration with Laravel/Lumen
✅ **Well Documented** - Comprehensive documentation and examples
✅ **Tested** - Full test coverage with PHPUnit

## Requirements

- PHP 8.1 or higher
- Guzzle HTTP Client ^7.0

## Installation

```bash
composer require artempuzik/sumsub-php-sdk
```

## Quick Start

```php
use SumsubSdk\Sumsub\Client\SumsubClient;

// Initialize client
$client = new SumsubClient(
    appToken: 'your_app_token',
    secretKey: 'your_secret_key'
);

// Generate access token for WebSDK
$tokenData = $client->generateAccessToken('user-123', 'basic-kyc-level');

// Get applicant data
$applicant = $client->getApplicantByExternalUserId('user-123');

// Check verification status
if ($applicant->getData()->isVerified()) {
    echo "User is verified!";
}

// Get documents
$documents = $client->getDocuments($applicant->getData()->id);
echo "Total documents: " . $documents->count();
echo "Approved: " . $documents->getApproved()->count();
```

## Documentation

- 📖 [Quick Start & Basic Usage](#quick-start)
- 🔧 [Laravel Integration](LARAVEL_SETUP.md)
- 🎯 [API Reference](API_REFERENCE.md)
- 🔔 [Events & Webhooks](EVENTS.md)
- 🔗 [Integration Guide](INTEGRATION.md)
- 📋 [Required Variables](REQUIRED_VARIABLES.md)
- 🧪 [Testing Guide](TESTING.md)

## Basic Usage

### Client Initialization

```php
use SumsubSdk\Sumsub\Client\SumsubClient;

$client = new SumsubClient(
    appToken: env('SUMSUB_APP_TOKEN'),
    secretKey: env('SUMSUB_APP_SECRET')
);
```

### Generate Access Token

```php
$tokenData = $client->generateAccessToken(
    externalUserId: 'user-123',
    levelName: 'basic-kyc-level'
);

echo $tokenData['token']; // Use in WebSDK
```

### Get Applicant Data

```php
$applicant = $client->getApplicantByExternalUserId('user-123');

// Structured data via toArray()
$data = $applicant->toArray();
echo $data['verification_status']; // 'verified', 'rejected', or 'pending'
echo $data['personal_info']['full_name'];

// Or access DTO directly
$dto = $applicant->getData();
if ($dto->isVerified()) {
    echo "Verified!";
}
```

### Get Documents

```php
$applicant = $client->getApplicantByExternalUserId('user-123');
$documents = $client->getDocuments($applicant->getData()->id);

// Filter documents
$approved = $documents->getApproved();
$rejected = $documents->getRejected();
$identityDocs = $documents->getByType('IDENTITY');

// Download document image
foreach ($documents->all() as $docResource) {
    $doc = $docResource->getData();
    $imageData = $client->getDocumentImage($applicant->getData()->id, $doc->imageId);
    file_put_contents("document_{$doc->imageId}.jpg", $imageData);
}
```

### Handle Webhooks

```php
use SumsubSdk\Sumsub\Webhooks\WebhookHandler;
use SumsubSdk\Sumsub\Events\ApplicantReviewed;

$handler = new WebhookHandler(env('SUMSUB_APP_SECRET'));

// Subscribe to events
$handler->getDispatcher()->listen(
    ApplicantReviewed::class,
    function(ApplicantReviewed $event) {
        if ($event->isApproved()) {
            // Update database
            User::where('referral_code', $event->getExternalUserId())
                ->update(['verified' => true]);
        }
    }
);

// Handle webhook (POST endpoint)
$webhook = $handler->handleFromRequest(
    headers: $request->headers->all(),
    body: $request->getContent()
);
```

## Event System

Subscribe to specific webhook events:

```php
use SumsubSdk\Sumsub\Events\{
    ApplicantCreated,
    ApplicantPending,
    ApplicantReviewed,
    ApplicantWorkflowCompleted
};

$dispatcher = $handler->getDispatcher();

// Listen to verification complete
$dispatcher->listen(ApplicantReviewed::class, function($event) {
    if ($event->isApproved()) {
        // User verified
    } elseif ($event->isRejected()) {
        // User rejected
    }
});

// Listen to workflow complete
$dispatcher->listen(ApplicantWorkflowCompleted::class, function($event) {
    // Final verification step completed
});
```

See [EVENTS.md](EVENTS.md) for complete event documentation.

## Laravel Integration

### 1. Install Package

```bash
composer require artempuzik/sumsub-php-sdk
```

### 2. Configure

```env
SUMSUB_APP_TOKEN=your_token
SUMSUB_APP_SECRET=your_secret
SUMSUB_LEVEL_NAME=basic-kyc-level
```

### 3. Use in Controllers

```php
use SumsubSdk\Sumsub\Client\SumsubClient;

class KycController extends Controller
{
    public function __construct(
        private SumsubClient $client
    ) {}

    public function verify(string $referralCode)
    {
        $tokenData = $this->client->generateAccessToken(
            externalUserId: $referralCode,
            levelName: 'basic-kyc-level'
        );

        return view('kyc.verify', [
            'token' => $tokenData['token']
        ]);
    }
}
```

See [LARAVEL_SETUP.md](LARAVEL_SETUP.md) for complete Laravel integration guide.

## Testing

```bash
# Install dev dependencies
composer install --dev

# Run all tests
composer test

# Run unit tests only
composer test:unit

# Run integration tests (requires API credentials)
export SUMSUB_APP_TOKEN="your_token"
export SUMSUB_APP_SECRET="your_secret"
composer test:integration

# Generate coverage report
composer test:coverage
```

See [TESTING.md](TESTING.md) for complete testing guide.

## Examples

Check the `/examples` directory for complete working examples:

- `examples/basic-usage.php` - Basic SDK usage
- `examples/webhook-handler.php` - Webhook handling
- `examples/webhook-with-events.php` - Event-driven webhooks
- `examples/laravel/` - Laravel integration examples

## API Methods

### Client Methods

- `generateAccessToken(string $externalUserId, string $levelName): array`
- `getApplicant(string $applicantId): ApplicantResource`
- `getApplicantByExternalUserId(string $externalUserId): ApplicantResource`
- `createApplicant(string $externalUserId, string $levelName, ?array $info = null): ApplicantResource`
- `getApplicantStatus(string $applicantId): array`
- `getDocuments(string $applicantId): DocumentCollection`
- `getDocumentImage(string $applicantId, string $imageId): string`
- `addDocument(string $applicantId, string $filePath, array $metadata): string`

### Resource Methods

**ApplicantResource:**
- `toArray(): array` - Get structured data
- `getData(): ApplicantData` - Get DTO

**DocumentCollection:**
- `count(): int` - Total documents
- `getApproved(): self` - Filter approved
- `getRejected(): self` - Filter rejected
- `getByType(string $type): self` - Filter by type
- `toArray(): array` - Convert to array
- `all(): array` - Get all resources

### DTO Helper Methods

**ApplicantData:**
- `isVerified(): bool`
- `isRejected(): bool`
- `isPending(): bool`

**DocumentData:**
- `isApproved(): bool`
- `isRejected(): bool`
- `requiresReview(): bool`

## Error Handling

```php
use SumsubSdk\Sumsub\Exceptions\{
    ApiException,
    ValidationException,
    WebhookException
};

try {
    $applicant = $client->getApplicantByExternalUserId('user-123');
} catch (ApiException $e) {
    echo "API Error: " . $e->getMessage();
    echo "Status Code: " . $e->getStatusCode();
    echo "Correlation ID: " . $e->getCorrelationId();
} catch (ValidationException $e) {
    echo "Validation Error: " . $e->getMessage();
    print_r($e->getErrors());
}
```

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Support

- 📚 [Sumsub Documentation](https://docs.sumsub.com/)
- 🔗 [Sumsub API Reference](https://docs.sumsub.com/reference)
- 💬 [GitHub Issues](https://github.com/artempuzik/sumsub-php-sdk/issues)

## Credits

Developed by [Artem Puzik](mailto:artem.puzik.it@gmail.com)
