# Testing Guide

Complete guide for testing Sumsub PHP SDK.

## Installation

Install dev dependencies:

```bash
composer install --dev
```

## Running Tests

### Run all tests

```bash
composer test
# or
vendor/bin/phpunit
```

### Run only unit tests

```bash
composer test:unit
# or
vendor/bin/phpunit --testsuite=Unit
```

### Run only feature tests

```bash
composer test:feature
# or
vendor/bin/phpunit --testsuite=Feature
```

### Run integration tests

Integration tests require real API credentials:

```bash
# Set credentials
export SUMSUB_APP_TOKEN="your_token"
export SUMSUB_APP_SECRET="your_secret"

# Run integration tests
composer test:integration
# or
vendor/bin/phpunit --group=integration
```

### Generate coverage report

```bash
composer test:coverage
# Report will be in ./coverage/index.html
```

## Test Structure

```
tests/
├── Unit/                       # Unit tests (no external dependencies)
│   ├── Enums/
│   │   ├── ReviewAnswerTest.php
│   │   └── ReviewStatusTest.php
│   ├── DataObjects/
│   │   ├── ApplicantDataTest.php
│   │   └── DocumentDataTest.php
│   ├── Resources/
│   │   └── DocumentCollectionTest.php
│   ├── Webhooks/
│   │   └── WebhookValidatorTest.php
│   └── Events/
│       └── EventDispatcherTest.php
└── Feature/                    # Feature tests (may use mocks)
    ├── SumsubClientTest.php    # Integration tests
    └── WebhookHandlerTest.php
```

## Unit Tests

Unit tests don't require external dependencies and run fast.

### Example: Testing Enums

```php
use PHPUnit\Framework\TestCase;
use SumsubSdk\Sumsub\Enums\ReviewAnswer;

class ReviewAnswerTest extends TestCase
{
    public function test_is_approved()
    {
        $this->assertTrue(ReviewAnswer::GREEN->isApproved());
        $this->assertFalse(ReviewAnswer::RED->isApproved());
    }
}
```

### Example: Testing Data Objects

```php
use PHPUnit\Framework\TestCase;
use SumsubSdk\Sumsub\DataObjects\ApplicantData;

class ApplicantDataTest extends TestCase
{
    public function test_from_array_creates_instance()
    {
        $data = [
            'id' => 'test-id',
            'externalUserId' => 'user-123',
        ];

        $applicant = ApplicantData::fromArray($data);

        $this->assertEquals('test-id', $applicant->id);
        $this->assertEquals('user-123', $applicant->externalUserId);
    }
}
```

## Feature Tests

Feature tests verify component interactions.

### Example: Testing Webhook Handler

```php
use PHPUnit\Framework\TestCase;
use SumsubSdk\Sumsub\Webhooks\WebhookHandler;

class WebhookHandlerTest extends TestCase
{
    public function test_handle_valid_webhook()
    {
        $handler = new WebhookHandler('secret_key');

        $payload = json_encode([
            'type' => 'applicantReviewed',
            'applicantId' => 'test-id',
            'externalUserId' => 'user-123',
        ]);

        $signature = hash_hmac('sha256', $payload, 'secret_key');

        $webhook = $handler->handle($payload, $signature);

        $this->assertEquals('test-id', $webhook->applicantId);
    }
}
```

## Integration Tests

Integration tests make real requests to Sumsub API.

**⚠️ Warning:** These tests create real applicant records in Sumsub.

### Setup

1. Get your credentials from [Sumsub Dashboard](https://cockpit.sumsub.com/)
2. Set environment variables:

```bash
export SUMSUB_APP_TOKEN="sbx:your_token"
export SUMSUB_APP_SECRET="your_secret"
```

### Running

```bash
vendor/bin/phpunit --group=integration
```

### Example

```php
/**
 * @group integration
 */
class SumsubClientTest extends TestCase
{
    public function test_generate_access_token()
    {
        $client = new SumsubClient(
            getenv('SUMSUB_APP_TOKEN'),
            getenv('SUMSUB_APP_SECRET')
        );

        $tokenData = $client->generateAccessToken('test-user', 'basic-kyc-level');

        $this->assertArrayHasKey('token', $tokenData);
        $this->assertNotEmpty($tokenData['token']);
    }
}
```

## Testing in Laravel

### Setup

```php
// tests/TestCase.php
use SumsubSdk\Sumsub\Client\SumsubClient;

abstract class TestCase extends BaseTestCase
{
    protected function mockSumsub()
    {
        $mock = \Mockery::mock(SumsubClient::class);
        $this->app->instance(SumsubClient::class, $mock);
        return $mock;
    }
}
```

### Example Test

```php
use Tests\TestCase;

class SumsubIntegrationTest extends TestCase
{
    public function test_can_generate_token()
    {
        $mock = $this->mockSumsub();

        $mock->shouldReceive('generateAccessToken')
            ->once()
            ->with('user-123', 'basic-kyc-level')
            ->andReturn(['token' => 'test_token']);

        $response = $this->post('/api/sumsub/token', [
            'referral_code' => 'user-123'
        ]);

        $response->assertOk();
        $response->assertJson(['token' => 'test_token']);
    }
}
```

## Mocking Examples

### Mock SumsubClient

```php
use Mockery;
use SumsubSdk\Sumsub\Client\SumsubClient;
use SumsubSdk\Sumsub\Resources\ApplicantResource;

$client = Mockery::mock(SumsubClient::class);

$client->shouldReceive('generateAccessToken')
    ->with('user-123', 'basic-kyc-level')
    ->andReturn(['token' => 'test_token']);

$client->shouldReceive('getApplicantByExternalUserId')
    ->with('user-123')
    ->andReturn(Mockery::mock(ApplicantResource::class));
```

### Mock WebhookHandler

```php
use Mockery;
use SumsubSdk\Sumsub\Webhooks\WebhookHandler;
use SumsubSdk\Sumsub\Webhooks\WebhookData;

$handler = Mockery::mock(WebhookHandler::class);

$handler->shouldReceive('handleFromRequest')
    ->andReturn(Mockery::mock(WebhookData::class));
```

## Continuous Integration

### GitHub Actions

```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest

    steps:
      - uses: actions/checkout@v3

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.1'
          extensions: mbstring, xml
          coverage: xdebug

      - name: Install dependencies
        run: composer install --prefer-dist --no-progress

      - name: Run unit tests
        run: composer test:unit

      - name: Run feature tests
        run: composer test:feature

      - name: Generate coverage
        run: composer test:coverage

      - name: Upload coverage
        uses: codecov/codecov-action@v3
        with:
          files: ./coverage/clover.xml
```

## Best Practices

### 1. Test Naming

```php
// Good - descriptive names
public function test_is_verified_returns_true_when_approved()

// Bad - vague names
public function testVerified()
```

### 2. Arrange-Act-Assert Pattern

```php
public function test_example()
{
    // Arrange - setup test data
    $data = ['id' => 'test-id'];

    // Act - perform the action
    $result = ApplicantData::fromArray($data);

    // Assert - verify the result
    $this->assertEquals('test-id', $result->id);
}
```

### 3. Use Data Providers

```php
/**
 * @dataProvider statusProvider
 */
public function test_status_check($status, $expected)
{
    $this->assertEquals($expected, $status->isCompleted());
}

public function statusProvider(): array
{
    return [
        'completed' => [ReviewStatus::COMPLETED, true],
        'pending' => [ReviewStatus::PENDING, false],
        'init' => [ReviewStatus::INIT, false],
    ];
}
```

### 4. Test Edge Cases

```php
public function test_handles_null_values()
public function test_handles_empty_array()
public function test_throws_exception_for_invalid_data()
```

### 5. Clean Up

```php
protected function tearDown(): void
{
    Mockery::close();
    parent::tearDown();
}
```

## Coverage Goals

- **Enums**: 100% coverage
- **Data Objects**: 100% coverage
- **Resources**: 90%+ coverage
- **Client**: 80%+ coverage (due to HTTP calls)
- **Validators**: 100% coverage
- **Events**: 90%+ coverage

## Troubleshooting

### Tests fail with "Class not found"

```bash
composer dump-autoload
```

### Integration tests skip

Ensure environment variables are set:

```bash
echo $SUMSUB_APP_TOKEN
echo $SUMSUB_APP_SECRET
```

### Coverage not generated

Install Xdebug:

```bash
pecl install xdebug
```

Or use PCOV:

```bash
composer require --dev pcov/clobber
vendor/bin/pcov clobber
```

## Additional Resources

- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Mockery Documentation](http://docs.mockery.io/)
- [Laravel Testing](https://laravel.com/docs/testing)
- [Sumsub API Documentation](https://docs.sumsub.com/)

## Running Specific Tests

### Run single test file

```bash
vendor/bin/phpunit tests/Unit/Enums/ReviewAnswerTest.php
```

### Run single test method

```bash
vendor/bin/phpunit --filter test_is_approved
```

### Run tests matching pattern

```bash
vendor/bin/phpunit --filter Applicant
```

## Debugging Tests

### Enable verbose output

```bash
vendor/bin/phpunit --verbose
```

### Stop on failure

```bash
vendor/bin/phpunit --stop-on-failure
```

### Print output

```php
public function test_example()
{
    var_dump($someVariable);
    $this->assertTrue(true);
}
```

Then run:

```bash
vendor/bin/phpunit --debug
```

