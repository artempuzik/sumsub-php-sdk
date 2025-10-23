# Changelog

## [1.0.0] - 2025-10-23

### Added
- Initial release
- Complete Sumsub API client
- Typed Data Objects (DTOs)
- Resources for data transformation
- Enums for type-safe constants
- Webhook handler with signature validation
- **Event system for webhook handling**
- **EventDispatcher for subscribing to events**
- **11 webhook events (ApplicantReviewed, ApplicantPending, etc.)**
- Exception handling with correlation IDs
- Full documentation and examples

### Features
- Get applicant data
- Get applicant by external user ID
- Create new applicant
- Get applicant status
- Get documents with metadata
- Download document images
- Generate WebSDK access tokens
- Handle webhooks securely
- Validate webhook signatures

### Data Objects
- ApplicantData
- ApplicantInfoData
- ReviewData
- ReviewResultData
- DocumentData
- AddressData
- WebhookData

### Enums
- ReviewAnswer (GREEN, RED, YELLOW)
- ReviewStatus
- DocumentType
- ApplicantType
- WebhookType

### Resources
- ApplicantResource
- DocumentResource
- DocumentCollection

### Events
- Event (base class)
- ApplicantCreated
- ApplicantPending
- ApplicantReviewed
- ApplicantOnHold
- ApplicantPersonalInfoChanged
- ApplicantReset
- ApplicantActionPending
- ApplicantActionReviewed
- ApplicantActionOnHold
- ApplicantWorkflowCompleted
- VideoIdentStatusChanged
- EventDispatcher

### Exceptions
- SumsubException
- ApiException
- ValidationException
- WebhookException

