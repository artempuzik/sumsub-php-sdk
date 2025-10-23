# Sumsub PHP SDK - Complete Library Overview

## 🎯 Purpose

Full-featured library for working with Sumsub KYC/AML API with support for:
- Typed data (DTOs)
- Events
- Validation
- Data transformation (Resources)
- Webhook handling

## 📁 Library Structure

```
src/
├── Client/
│   └── SumsubClient.php              # Main API client
├── DataObjects/                       # Data Transfer Objects (DTOs)
│   ├── ApplicantData.php             # Applicant data
│   ├── ApplicantInfoData.php         # Personal information
│   ├── ReviewData.php                 # Review data
│   ├── ReviewResultData.php           # Review result
│   ├── DocumentData.php               # Document data
│   └── AddressData.php                # Address
├── Resources/                         # Data transformers
│   ├── ApplicantResource.php         # Applicant resource
│   ├── DocumentResource.php           # Document resource
│   └── DocumentCollection.php         # Document collection
├── Enums/                            # Enumerations
│   ├── ReviewAnswer.php              # GREEN, RED, YELLOW
│   ├── ReviewStatus.php              # pending, completed, etc.
│   ├── DocumentType.php              # IDENTITY, SELFIE, etc.
│   ├── ApplicantType.php             # individual, company
│   └── WebhookType.php               # Webhook event types
├── Events/                           # Events
│   ├── Event.php                     # Base event class
│   ├── EventDispatcher.php           # Event manager
│   ├── ApplicantCreated.php          # Created event
│   ├── ApplicantReviewed.php         # Reviewed event
│   ├── ApplicantPending.php          # Pending event
│   └── ...                           # + 8 other events
├── Webhooks/                         # Webhook handling
│   ├── WebhookHandler.php            # Webhook handler
│   └── WebhookData.php               # Webhook data
├── Validators/                       # Validators
│   └── WebhookValidator.php          # Webhook validation
└── Exceptions/                       # Exceptions
    ├── SumsubException.php           # Base exception
    ├── ApiException.php              # API errors
    ├── ValidationException.php        # Validation errors
    └── WebhookException.php          # Webhook errors
```

## 🔧 Components

### 1. Client (API Client)

**SumsubClient** - main class for working with Sumsub API

**Methods:**
- `getApplicant($applicantId)` - Get applicant by ID
- `getApplicantByExternalUserId($externalUserId)` - By external ID
- `createApplicant($externalUserId, $levelName, $info)` - Create
- `getApplicantStatus($applicantId)` - Verification status
- `getDocuments($applicantId)` - Get documents
- `getDocumentImage($applicantId, $imageId)` - Download image
- `generateAccessToken($externalUserId, $levelName)` - WebSDK token
- `addDocument($applicantId, $filePath, $metadata)` - Upload document

### 2. Data Objects (DTOs)

**Purpose:** Typed, immutable data objects

**ApplicantData:**
```php
$applicant->id;              // string
$applicant->externalUserId;  // string
$applicant->type;            // ApplicantType enum
$applicant->info;            // ApplicantInfoData
$applicant->review;          // ReviewData
$applicant->isVerified();    // bool
$applicant->isRejected();    // bool
```

**DocumentData:**
```php
$document->imageId;          // string
$document->docSetType;       // string
$document->country;          // string
$document->reviewAnswer;     // ReviewAnswer enum
$document->isApproved();     // bool
```

### 3. Resources (Transformers)

**Purpose:** Transform raw API data into convenient format

**ApplicantResource:**
```php
$resource = ApplicantResource::fromArray($apiData);

$resource->toArray(); // Структурированный массив
$resource->getData(); // ApplicantData object

// Example toArray() output:
[
    'id' => '...',
    'external_user_id' => '...',
    'verification_status' => 'verified', // or 'rejected', 'pending'
    'is_verified' => true,
    'personal_info' => [
        'full_name' => 'John Doe',
        'email' => '...',
        'country' => '...',
        'address' => [...]
    ],
    'review' => [...]
]
```

**DocumentCollection:**
```php
$documents = DocumentCollection::make($documentsArray);

$documents->count();              // Всего документов
$documents->getApproved();        // Только одобренные
$documents->getRejected();        // Только отклоненные
$documents->getByType('IDENTITY'); // По типу
$documents->toArray();            // В массив
```

### 4. Enums (Перечисления)

**Назначение:** Типобезопасные константы

**ReviewAnswer:**
```php
ReviewAnswer::GREEN;   // Approved
ReviewAnswer::RED;     // Rejected
ReviewAnswer::YELLOW;  // Requires review

$answer->isApproved();     // bool
$answer->getLabel();       // 'Approved'
```

**DocumentType:**
```php
DocumentType::IDENTITY;
DocumentType::SELFIE;
DocumentType::PASSPORT;
DocumentType::ID_CARD;

$type->isIdentityDocument();     // bool
$type->requiresMultipleSides();  // bool
```

### 5. Events

**Purpose:** Subscribe to webhook events

**EventDispatcher:**
```php
$dispatcher = new EventDispatcher();

// Subscribe to event
$dispatcher->listen(ApplicantReviewed::class, function($event) {
    // Handle event
});

// Dispatch event
$dispatcher->dispatch($event);
```

**Available events:**
1. `ApplicantCreated` - Applicant created
2. `ApplicantPending` - Pending review
3. `ApplicantReviewed` - Review completed ⭐
4. `ApplicantOnHold` - On hold
5. `ApplicantPersonalInfoChanged` - Information changed
6. `ApplicantReset` - Reset
7. `ApplicantActionPending` - Action pending
8. `ApplicantActionReviewed` - Action reviewed
9. `ApplicantActionOnHold` - Action on hold
10. `ApplicantWorkflowCompleted` - Workflow completed ⭐
11. `VideoIdentStatusChanged` - Video status changed

**Event methods:**
```php
$event->getApplicantId();        // string
$event->getExternalUserId();     // string
$event->getWebhookData();        // WebhookData

// Для ApplicantReviewed:
$event->isApproved();            // bool
$event->isRejected();            // bool
$event->requiresReview();        // bool
```

### 6. Webhooks (Обработка webhook'ов)

**WebhookHandler:**
```php
$handler = new WebhookHandler($secretKey);

// Handle webhook
$webhook = $handler->handle($payload, $signature, $algorithm);

// Or from HTTP request
$webhook = $handler->handleFromRequest($headers, $body);

// Work with events
$dispatcher = $handler->getDispatcher();
$dispatcher->listen(ApplicantReviewed::class, $callback);
```

**WebhookData:**
```php
$webhook->type;             // WebhookType enum
$webhook->applicantId;      // string
$webhook->externalUserId;   // string
$webhook->reviewStatus;     // string
$webhook->reviewAnswer;     // ReviewAnswer enum

$webhook->isApproved();     // bool
$webhook->isRejected();     // bool
```

### 7. Validators

**WebhookValidator:**
```php
$validator = new WebhookValidator($secretKey);

// Проверить подпись
$validator->validateSignature($payload, $signature, 'sha256');

// Проверить структуру
$validator->validatePayload($data);
```

### 8. Exceptions (Исключения)

**ApiException:**
```php
try {
    $client->getApplicant('invalid-id');
} catch (ApiException $e) {
    $e->getMessage();         // Error message
    $e->getStatusCode();      // HTTP code
    $e->getCorrelationId();   // ID for debugging
    $e->getResponseData();    // Full response
}
```

**ValidationException:**
```php
try {
    $handler->handle($payload, $signature);
} catch (ValidationException $e) {
    $e->getMessage();         // Текст ошибки
    $e->getErrors();          // Массив ошибок
}
```

## 📖 Примеры использования

### Пример 1: Получение данных пользователя

```php
use SumsubSdk\Sumsub\Client\SumsubClient;

$client = new SumsubClient($appToken, $secretKey);

// Получить аппликанта
$applicant = $client->getApplicantByExternalUserId('user-123');

// Проверить статус
if ($applicant->getData()->isVerified()) {
    echo "✅ Пользователь верифицирован";
}

// Получить данные
$data = $applicant->toArray();
echo "Имя: {$data['personal_info']['full_name']}";
echo "Статус: {$data['verification_status']}";
```

### Пример 2: Работа с документами

```php
// Получить документы
$documents = $client->getDocuments($applicantId);

// Статистика
echo "Всего: {$documents->count()}";
echo "Одобрено: {$documents->getApproved()->count()}";

// Получить только паспорта
$identityDocs = $documents->getByType('IDENTITY');

// Скачать изображение
foreach ($documents->all() as $docResource) {
    $doc = $docResource->getData();
    $image = $client->getDocumentImage($applicantId, $doc->imageId);
    file_put_contents("doc_{$doc->imageId}.jpg", $image);
}
```

### Пример 3: Webhook с событиями

```php
$handler = new WebhookHandler($secretKey);
$dispatcher = $handler->getDispatcher();

// Подписка на одобрение
$dispatcher->listen(ApplicantReviewed::class, function($event) {
    if ($event->isApproved()) {
        // Обновить БД
        User::where('external_id', $event->getExternalUserId())
            ->update(['verified' => true]);

        // Отправить email
        Mail::to($user)->send(new VerifiedEmail());
    }
});

// Обработать webhook
$webhook = $handler->handleFromRequest($headers, $body);
// События отправятся автоматически
```

### Пример 4: Laravel интеграция

```php
// Service Provider
$this->app->singleton(SumsubClient::class, function() {
    return new SumsubClient(
        config('services.sumsub.token'),
        config('services.sumsub.secret')
    );
});

// Controller
public function getUser(SumsubClient $client, $userId) {
    $applicant = $client->getApplicantByExternalUserId($userId);
    return response()->json($applicant->toArray());
}

// Webhook Controller
public function webhook(Request $request, WebhookHandler $handler) {
    $webhook = $handler->handleFromRequest(
        $request->headers->all(),
        $request->getContent()
    );

    // События будут отправлены автоматически
    return response()->json(['status' => 'ok']);
}
```

## 🎯 Преимущества библиотеки

1. **Type Safety** - PHP 8.1+ enums, readonly properties
2. **Convenient Methods** - `isVerified()`, `isApproved()` instead of string comparison
3. **Events** - Subscribe to webhook events for clean code
4. **DTOs** - Structured data instead of arrays
5. **Resources** - Automatic API data transformation
6. **Validation** - Automatic webhook signature verification
7. **Exceptions** - Detailed error information with correlation IDs
8. **Zero Config** - Works out of the box, easy integration
9. **Extensibility** - Easy to add custom event handlers
10. **Documentation** - Complete examples and documentation

## 📦 Installation

```bash
composer require artempuzik/sumsub-php-sdk
```

## 🔗 Useful Files

- `README.md` - Main documentation
- `INTEGRATION.md` - Laravel integration
- `CHANGELOG.md` - Change history
- `examples/basic-usage.php` - Basic examples
- `examples/webhook-with-events.php` - Working with events
- `examples/events-laravel.php` - Laravel integration

## 📞 Support

- GitHub Issues
- Sumsub Documentation: https://docs.sumsub.com/
- API Reference: https://docs.sumsub.com/reference

---

**Version:** 1.0.0
**PHP:** ^8.1
**License:** MIT

