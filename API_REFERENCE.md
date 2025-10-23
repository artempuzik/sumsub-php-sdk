# API Reference

Complete reference for all classes, methods, and types in the Sumsub PHP SDK.

## Client

### SumsubClient

Main client for interacting with Sumsub API.

```php
class SumsubClient
{
    public function __construct(
        string $appToken,
        string $secretKey,
        string $baseUrl = 'https://api.sumsub.com'
    )
}
```

#### Methods

##### getApplicant()

Get applicant by Sumsub applicant ID.

```php
public function getApplicant(string $applicantId): ApplicantResource

// Example
$applicant = $client->getApplicant('5f7c3b8e4c9b5d001f3e4e5f');
```

##### getApplicantByExternalUserId()

Get applicant by your internal user ID.

```php
public function getApplicantByExternalUserId(string $externalUserId): ApplicantResource

// Example
$applicant = $client->getApplicantByExternalUserId('user-123');
```

##### createApplicant()

Create new applicant.

```php
public function createApplicant(
    string $externalUserId,
    string $levelName,
    ?array $info = null
): ApplicantResource

// Example
$applicant = $client->createApplicant(
    externalUserId: 'user-123',
    levelName: 'basic-kyc-level',
    info: [
        'firstName' => 'John',
        'lastName' => 'Doe',
        'dob' => '1990-01-15'
    ]
);
```

##### getApplicantStatus()

Get applicant verification status.

```php
public function getApplicantStatus(string $applicantId): array

// Example
$status = $client->getApplicantStatus('5f7c3b8e4c9b5d001f3e4e5f');
// Returns: ['IDENTITY' => ['imageIds' => [...], 'reviewResult' => ...]]
```

##### getDocuments()

Get all documents for applicant.

```php
public function getDocuments(string $applicantId): DocumentCollection

// Example
$documents = $client->getDocuments('5f7c3b8e4c9b5d001f3e4e5f');
echo $documents->count();
```

##### getDocumentImage()

Get document image as binary data.

```php
public function getDocumentImage(string $applicantId, string $imageId): string

// Example
$imageData = $client->getDocumentImage('5f7c3b8e4c9b5d001f3e4e5f', 'image-123');
file_put_contents('document.jpg', $imageData);
```

##### generateAccessToken()

Generate access token for WebSDK.

```php
public function generateAccessToken(string $externalUserId, string $levelName): array

// Example
$tokenData = $client->generateAccessToken('user-123', 'basic-kyc-level');
echo $tokenData['token'];
```

##### addDocument()

Upload document for applicant.

```php
public function addDocument(
    string $applicantId,
    string $filePath,
    array $metadata
): string

// Example
$imageId = $client->addDocument(
    applicantId: '5f7c3b8e4c9b5d001f3e4e5f',
    filePath: '/path/to/document.jpg',
    metadata: ['idDocType' => 'PASSPORT', 'country' => 'USA']
);
```

## Data Objects

### ApplicantData

```php
readonly class ApplicantData
{
    public string $id;
    public string $externalUserId;
    public string $createdAt;
    public ?string $inspectionId;
    public ?ApplicantType $type;
    public ?string $lang;
    public ?ApplicantInfoData $info;
    public ?ReviewData $review;
    public ?array $requiredIdDocs;
    public ?array $rawData;

    public function isVerified(): bool
    public function isRejected(): bool
    public function isPending(): bool
}
```

### ApplicantInfoData

```php
readonly class ApplicantInfoData
{
    public ?string $firstName;
    public ?string $lastName;
    public ?string $middleName;
    public ?string $dob;
    public ?string $country;
    public ?string $nationality;
    public ?string $phone;
    public ?string $email;
    public ?AddressData $address;
    public ?array $rawData;

    public function getFullName(): string
}
```

### ReviewData

```php
readonly class ReviewData
{
    public ?string $reviewId;
    public ?string $attemptId;
    public ?int $attemptCnt;
    public ?string $levelName;
    public ?string $createDate;
    public ?string $reviewDate;
    public ?ReviewStatus $reviewStatus;
    public ?ReviewResultData $reviewResult;
    public ?int $priority;
    public ?array $rawData;

    public function isCompleted(): bool
    public function isPending(): bool
    public function isApproved(): bool
    public function isRejected(): bool
}
```

### ReviewResultData

```php
readonly class ReviewResultData
{
    public ?ReviewAnswer $reviewAnswer;
    public ?string $moderationComment;
    public ?string $clientComment;
    public ?string $reviewRejectType;
    public ?array $rejectLabels;
    public ?array $rawData;

    public function isApproved(): bool
    public function isRejected(): bool
    public function requiresReview(): bool
}
```

### DocumentData

```php
readonly class DocumentData
{
    public string $imageId;
    public string $docSetType;
    public ?string $idDocType;
    public ?string $country;
    public ?ReviewAnswer $reviewAnswer;
    public ?string $attemptId;
    public ?array $rawData;

    public function isApproved(): bool
    public function isRejected(): bool
    public function requiresReview(): bool
}
```

### WebhookData

```php
readonly class WebhookData
{
    public WebhookType $type;
    public string $applicantId;
    public string $externalUserId;
    public ?string $inspectionId;
    public ?string $correlationId;
    public ?string $reviewStatus;
    public ?ReviewAnswer $reviewAnswer;
    public ?string $applicantType;
    public ?string $createdAt;
    public ?array $reviewResult;
    public ?array $rawPayload;

    public function isApproved(): bool
    public function isRejected(): bool
    public function requiresReview(): bool
    public function isReviewCompleted(): bool
}
```

## Resources

### ApplicantResource

```php
class ApplicantResource
{
    public function toArray(): array
    public function getData(): ApplicantData
}
```

**toArray() structure:**
```php
[
    'id' => string,
    'external_user_id' => string,
    'created_at' => string,
    'type' => string,
    'verification_status' => 'verified'|'rejected'|'pending',
    'is_verified' => bool,
    'is_rejected' => bool,
    'is_pending' => bool,
    'personal_info' => [
        'full_name' => string,
        'first_name' => string,
        'last_name' => string,
        'date_of_birth' => string,
        'country' => string,
        'phone' => string,
        'email' => string,
        'address' => [...]
    ],
    'review' => [
        'review_id' => string,
        'status' => string,
        'result' => [...]
    ]
]
```

### DocumentResource

```php
class DocumentResource
{
    public function toArray(): array
    public function getData(): DocumentData
}
```

**toArray() structure:**
```php
[
    'image_id' => string,
    'type' => string,
    'document_type' => string,
    'country' => string,
    'review_status' => 'green'|'red'|'yellow',
    'is_approved' => bool,
    'is_rejected' => bool,
    'requires_review' => bool
]
```

### DocumentCollection

```php
class DocumentCollection
{
    public function toArray(): array
    public function count(): int
    public function getApproved(): self
    public function getRejected(): self
    public function getByType(string $type): self
    public function all(): array
}
```

## Enums

### ReviewAnswer

```php
enum ReviewAnswer: string
{
    case GREEN = 'GREEN';
    case RED = 'RED';
    case YELLOW = 'YELLOW';

    public function isApproved(): bool
    public function isRejected(): bool
    public function requiresReview(): bool
    public function getLabel(): string
}
```

### ReviewStatus

```php
enum ReviewStatus: string
{
    case INIT = 'init';
    case PENDING = 'pending';
    case PRECHECKED = 'prechecked';
    case QUEUED = 'queued';
    case COMPLETED = 'completed';
    case ON_HOLD = 'onHold';

    public function isCompleted(): bool
    public function isPending(): bool
}
```

### DocumentType

```php
enum DocumentType: string
{
    case IDENTITY = 'IDENTITY';
    case SELFIE = 'SELFIE';
    case VIDEO_SELFIE = 'VIDEO_SELFIE';
    case PROOF_OF_RESIDENCE = 'PROOF_OF_RESIDENCE';
    case PASSPORT = 'PASSPORT';
    case ID_CARD = 'ID_CARD';
    case DRIVERS = 'DRIVERS';
    // ... and more

    public function isIdentityDocument(): bool
    public function requiresMultipleSides(): bool
}
```

### ApplicantType

```php
enum ApplicantType: string
{
    case INDIVIDUAL = 'individual';
    case COMPANY = 'company';

    public function isIndividual(): bool
    public function isCompany(): bool
}
```

### WebhookType

```php
enum WebhookType: string
{
    case APPLICANT_CREATED = 'applicantCreated';
    case APPLICANT_PENDING = 'applicantPending';
    case APPLICANT_REVIEWED = 'applicantReviewed';
    // ... and more

    public function isReview(): bool
    public function isPending(): bool
    public function isCompleted(): bool
}
```

## Exceptions

### ApiException

```php
class ApiException extends SumsubException
{
    public function getStatusCode(): int
    public function getResponseData(): ?array
    public function getCorrelationId(): ?string
}
```

### ValidationException

```php
class ValidationException extends SumsubException
{
    public function getErrors(): array
}
```

### WebhookException

```php
class WebhookException extends SumsubException
{
}
```

## Webhook Handler

### WebhookHandler

```php
class WebhookHandler
{
    public function __construct(string $secretKey)

    public function handle(
        string $payload,
        string $signature,
        string $algorithm = 'sha256'
    ): WebhookData

    public function handleFromRequest(
        array $headers,
        string $body
    ): WebhookData

    public function setDispatcher(EventDispatcher $dispatcher): self
    public function getDispatcher(): EventDispatcher
}
```

## Validators

### WebhookValidator

```php
class WebhookValidator
{
    public function __construct(string $secretKey)

    public function validateSignature(
        string $payload,
        string $receivedSignature,
        string $algorithm = 'sha256'
    ): bool

    public function validatePayload(array $payload): bool
}
```

## Type Definitions

### Metadata for addDocument()

```php
[
    'idDocType' => 'PASSPORT'|'ID_CARD'|'DRIVERS'|...,
    'country' => 'USA'|'DEU'|...  // ISO 3-letter country code
]
```

### Info for createApplicant()

```php
[
    'firstName' => string,
    'lastName' => string,
    'middleName' => string (optional),
    'dob' => string,  // YYYY-MM-DD
    'country' => string,  // ISO 3-letter code
    'nationality' => string (optional),
    'phone' => string (optional),
    'email' => string (optional),
]
```

## Constants

### Verification Levels

Common verification level names:
- `basic-kyc-level`
- `advanced-kyc-level`
- `basic-kyb-level`

(Actual names depend on your Sumsub configuration)

### HTTP Status Codes

- `200` - Success
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized
- `403` - Forbidden (no permissions)
- `404` - Not Found
- `500` - Internal Server Error

## Links

- [Sumsub API Documentation](https://docs.sumsub.com/)
- [Sumsub API Reference](https://docs.sumsub.com/reference)
- [Webhook Documentation](https://docs.sumsub.com/reference/webhooks)

