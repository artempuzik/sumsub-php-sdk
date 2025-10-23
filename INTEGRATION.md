# Integration Guide

Как подключить библиотеку к проекту 1go.exchange

## Установка

### Вариант 1: Локальная установка (разработка)

1. Добавьте в `composer.json` проекта `1go.exchange`:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "../sumsub-php-sdk"
        }
    ],
    "require": {
        "artempuzik/sumsub-php-sdk": "*"
    }
}
```

2. Установите пакет:

```bash
cd /Users/artempuzik/work/SumsubSdk/1go.exchange
composer require artempuzik/sumsub-php-sdk
```

### Вариант 2: Через GitHub (production)

1. Загрузите код на GitHub
2. Добавьте в `composer.json`:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/artempuzik/sumsub-php-sdk"
        }
    ],
    "require": {
        "artempuzik/sumsub-php-sdk": "^1.0"
    }
}
```

## Использование в Laravel

### 1. Создайте Service Provider

```php
// app/Providers/SumsubServiceProvider.php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use SumsubSdk\Sumsub\Client\SumsubClient;
use SumsubSdk\Sumsub\Webhooks\WebhookHandler;

class SumsubServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Register Sumsub Client
        $this->app->singleton(SumsubClient::class, function ($app) {
            return new SumsubClient(
                appToken: config('sumsub.token'),
                secretKey: config('sumsub.secret')
            );
        });

        // Register Webhook Handler
        $this->app->singleton(WebhookHandler::class, function ($app) {
            return new WebhookHandler(
                secretKey: config('sumsub.secret')
            );
        });
    }
}
```

### 2. Зарегистрируйте Provider

```php
// config/app.php

'providers' => [
    // ...
    App\Providers\SumsubServiceProvider::class,
],
```

### 3. Обновите Controller

```php
// app/Http/Controllers/Api/v1/SumsubController.php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use SumsubSdk\Sumsub\Client\SumsubClient;
use SumsubSdk\Sumsub\Webhooks\WebhookHandler;
use SumsubSdk\Sumsub\Exceptions\ApiException;
use SumsubSdk\Sumsub\Exceptions\ValidationException;

class SumsubController extends Controller
{
    public function __construct(
        private SumsubClient $client,
        private WebhookHandler $webhookHandler
    ) {}

    public function getApplicantByReferralCode(string $referralCode)
    {
        try {
            $applicant = $this->client->getApplicantByExternalUserId($referralCode);

            return response()->json([
                'success' => true,
                'data' => $applicant->toArray()
            ]);

        } catch (ApiException $e) {
            \Log::error('Sumsub API error', [
                'referral_code' => $referralCode,
                'error' => $e->getMessage(),
                'correlation_id' => $e->getCorrelationId(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $e->getStatusCode());
        }
    }

    public function getDocumentsByReferralCode(string $referralCode)
    {
        try {
            $applicant = $this->client->getApplicantByExternalUserId($referralCode);
            $documents = $this->client->getDocuments($applicant->getData()->id);

            return response()->json([
                'success' => true,
                'data' => [
                    'applicant_id' => $applicant->getData()->id,
                    'total' => $documents->count(),
                    'approved' => $documents->getApproved()->count(),
                    'documents' => $documents->toArray()
                ]
            ]);

        } catch (ApiException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $e->getStatusCode());
        }
    }

    public function webhook(Request $request)
    {
        try {
            $webhook = $this->webhookHandler->handleFromRequest(
                headers: $request->headers->all(),
                body: $request->getContent()
            );

            // Update user in database
            $user = \App\Models\User\User::where('referral_code', $webhook->externalUserId)->first();

            if ($user) {
                $user->update([
                    'sumsub_applicant_id' => $webhook->applicantId,
                    'sumsub_review_status' => $webhook->reviewStatus,
                    'sumsub_applicant_status' => $webhook->isApproved() ? 'approved' :
                        ($webhook->isRejected() ? 'rejected' : 'pending'),
                    'kyc_verified_at' => $webhook->isApproved() ? now() : null,
                ]);
            }

            return response()->json(['status' => 'ok']);

        } catch (ValidationException $e) {
            \Log::warning('Invalid webhook signature', [
                'error' => $e->getMessage()
            ]);

            return response()->json(['error' => 'Invalid signature'], 400);

        } catch (\Exception $e) {
            \Log::error('Webhook processing error', [
                'error' => $e->getMessage()
            ]);

            return response()->json(['error' => 'Processing failed'], 500);
        }
    }
}
```

### 4. Создайте новый Service класс (опционально)

```php
// app/Services/KycVerificationService.php

namespace App\Services;

use App\Models\User\User;
use SumsubSdk\Sumsub\Client\SumsubClient;
use SumsubSdk\Sumsub\Resources\ApplicantResource;
use SumsubSdk\Sumsub\Resources\DocumentCollection;
use SumsubSdk\Sumsub\Exceptions\ApiException;

class KycVerificationService
{
    public function __construct(
        private SumsubClient $client
    ) {}

    public function getVerificationStatus(User $user): array
    {
        try {
            $applicant = $this->client->getApplicantByExternalUserId($user->referral_code);

            return [
                'status' => $applicant->toArray()['verification_status'],
                'is_verified' => $applicant->getData()->isVerified(),
                'is_rejected' => $applicant->getData()->isRejected(),
                'is_pending' => $applicant->getData()->isPending(),
                'applicant_id' => $applicant->getData()->id,
            ];

        } catch (ApiException $e) {
            \Log::error('Failed to get verification status', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => 'unknown',
                'error' => $e->getMessage(),
            ];
        }
    }

    public function getUserDocuments(User $user): ?DocumentCollection
    {
        try {
            $applicant = $this->client->getApplicantByExternalUserId($user->referral_code);
            return $this->client->getDocuments($applicant->getData()->id);
        } catch (ApiException $e) {
            \Log::error('Failed to get documents', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}
```

## Миграция существующего кода

### До (старый код):

```php
$sumsubService = new App\Services\Sumsub\SumsubService();
$data = $sumsubService->getApplicantByExternalUserId($referralCode);
```

### После (новая библиотека):

```php
$client = app(SumsubSdk\Sumsub\Client\SumsubClient::class);
$applicant = $client->getApplicantByExternalUserId($referralCode);
$data = $applicant->toArray(); // Structured data
```

## Преимущества новой библиотеки

1. **Типобезопасность** - Enums вместо строк
2. **Структурированные данные** - DTOs вместо массивов
3. **Удобные методы** - `isVerified()`, `isRejected()`, `isPending()`
4. **Валидация webhook** - Автоматическая проверка подписей
5. **Обработка ошибок** - Детальные исключения с correlation IDs
6. **Фильтрация** - `getApproved()`, `getByType()` для документов

## Пример использования в Blade

```php
@php
    $client = app(\SumsubSdk\Sumsub\Client\SumsubClient::class);
    try {
        $applicant = $client->getApplicantByExternalUserId(auth()->user()->referral_code);
        $data = $applicant->toArray();
    } catch (\Exception $e) {
        $data = null;
    }
@endphp

@if($data && $data['is_verified'])
    <div class="alert alert-success">
        ✅ Your account is verified!
    </div>
@elseif($data && $data['is_rejected'])
    <div class="alert alert-danger">
        ❌ Verification rejected. Please contact support.
    </div>
@else
    <div class="alert alert-info">
        ⏳ Verification in progress...
    </div>
@endif
```

## Testing

```php
// tests/Feature/SumsubIntegrationTest.php

namespace Tests\Feature;

use Tests\TestCase;
use SumsubSdk\Sumsub\Client\SumsubClient;

class SumsubIntegrationTest extends TestCase
{
    public function test_can_get_applicant()
    {
        $client = app(SumsubClient::class);

        $applicant = $client->getApplicantByExternalUserId('test-user-123');

        $this->assertNotNull($applicant);
        $this->assertEquals('test-user-123', $applicant->getData()->externalUserId);
    }
}
```

## Поддержка

Если возникнут вопросы при интеграции, проверьте:
- Файлы в `/examples/`
- README.md библиотеки
- CHANGELOG.md для списка всех функций

