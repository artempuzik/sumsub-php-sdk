# Portal Data Format

Documentation for exporting Sumsub data in Portal format.

## Overview

The SDK provides a specialized service for transforming Sumsub applicant data into a format suitable for external portal integration.

## Output Format

```json
{
    "user_xid": "{{$randomUUID}}",
    "email": "sdk_jane.doe@example.com",
    "user_name": "johndoe",
    "individual": {
        "first_name": "John",
        "last_name": "Doe",
        "date_of_birth": "1990-05-15",
        "occupation": "Software Engineer",
        "annual_income": "100000"
    },
    "address": {
        "country": "US",
        "city": "New York",
        "post_code": "10001",
        "details": "123 Main Street, Apt 4B"
    },
    "document": {
        "type": "2",
        "number": "P12345678",
        "country": "US",
        "expiry_date": "2030-12-31",
        "front": "data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEASABIAAD...",
        "back": "data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEASABIAAD...",
        "face": "data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEASABIAAD..."
    }
}
```

## Field Mapping

### Sumsub → Portal

| Portal Field | Sumsub Source | Notes |
|-------------|---------------|-------|
| `user_xid` | `externalUserId` | Your internal referral code/user ID |
| `email` | `info.email` | User's email |
| `user_name` | `info.firstName + lastName` | Auto-generated, lowercase |
| `individual.first_name` | `info.firstName` | |
| `individual.last_name` | `info.lastName` | |
| `individual.date_of_birth` | `info.dob` | Format: YYYY-MM-DD |
| `individual.occupation` | N/A | Not available in Sumsub |
| `individual.annual_income` | N/A | Not available in Sumsub |
| `address.country` | `info.addresses[0].country` | ISO code |
| `address.city` | `info.addresses[0].town` | |
| `address.post_code` | `info.addresses[0].postCode` | |
| `address.details` | Concatenated from street, building, flat | |
| `document.type` | Mapped from `idDocType` | See mapping below |
| `document.number` | N/A | Not available via API |
| `document.country` | Document country | ISO code |
| `document.expiry_date` | N/A | Not available via API |
| `document.front` | First IDENTITY image | Base64 encoded |
| `document.back` | Second IDENTITY image | Base64 encoded |
| `document.face` | First SELFIE image | Base64 encoded |

### Document Type Mapping

| Sumsub Type | Portal Type Code |
|-------------|------------------|
| `PASSPORT` | `1` |
| `ID_CARD`, `IDENTITY` | `2` |
| `DRIVERS`, `DRIVERS_LICENSE` | `3` |
| Other | `0` |

## Usage

### Basic Usage

```php
use SumsubSdk\Sumsub\Client\SumsubClient;
use SumsubSdk\Sumsub\Services\PortalDataService;

$client = new SumsubClient($token, $secret);
$portalService = new PortalDataService($client);

// Get data without images (faster)
$data = $portalService->getUserData('USER123', includeImages: false);

// Get data with images (slower, larger payload)
$dataWithImages = $portalService->getUserData('USER123', includeImages: true);
```

### Laravel Controller

```php
use SumsubSdk\Sumsub\Services\PortalDataService;

class PortalExportController extends Controller
{
    public function export(string $referralCode, PortalDataService $service)
    {
        $data = $service->getUserData($referralCode, includeImages: true);

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }
}
```

### Laravel Routes

```php
use App\Http\Controllers\Api\PortalExportController;

Route::prefix('api/portal')->group(function () {
    // Single user export
    Route::get('/export/{referralCode}', [PortalExportController::class, 'export']);

    // Bulk export
    Route::post('/export/bulk', [PortalExportController::class, 'bulkExport']);

    // Download as JSON file
    Route::get('/download/{referralCode}', [PortalExportController::class, 'download']);
});
```

## API Endpoints

### 1. Get Single User Data

```bash
GET /api/portal/export/{referralCode}?include_images=true
```

**Response:**
```json
{
    "success": true,
    "data": {
        "user_xid": "USER123",
        "email": "user@example.com",
        "user_name": "johndoe",
        "individual": {...},
        "address": {...},
        "document": {...}
    }
}
```

### 2. Bulk Export

```bash
POST /api/portal/export/bulk
Content-Type: application/json

{
    "referral_codes": ["USER123", "USER456"],
    "include_images": false
}
```

**Response:**
```json
{
    "success": true,
    "total": 2,
    "data": {
        "USER123": {...},
        "USER456": {...}
    }
}
```

### 3. Download as File

```bash
GET /api/portal/download/USER123?include_images=false
```

Downloads JSON file: `user_USER123.json`

## Performance Considerations

### Without Images (Recommended for lists)
- Fast response (~200-500ms)
- Small payload (~2-5KB)
- Use for: user lists, bulk exports, real-time status checks

### With Images
- Slower response (~2-5 seconds)
- Large payload (~100KB - 2MB depending on image quality)
- Use for: single user details, final export, document verification

## Examples

### Example 1: Export to External System

```php
$portalService = new PortalDataService($client);

// Get verified users from database
$verifiedUsers = User::where('verification_status', 'verified')->get();

foreach ($verifiedUsers as $user) {
    try {
        // Get portal format data
        $portalData = $portalService->getUserData($user->referral_code, false);

        // Send to external portal
        Http::post('https://external-portal.com/api/users', $portalData);

    } catch (\Exception $e) {
        \Log::error('Failed to export user', [
            'user_id' => $user->id,
            'error' => $e->getMessage()
        ]);
    }
}
```

### Example 2: API Endpoint for Portal

```php
// Add to your Laravel controller
public function getPortalData(string $referralCode, PortalDataService $service)
{
    try {
        $data = $service->getUserData($referralCode, includeImages: true);

        return response()->json($data);

    } catch (ApiException $e) {
        return response()->json([
            'error' => $e->getMessage()
        ], $e->getStatusCode());
    }
}
```

### Example 3: Queue Job for Bulk Export

```php
use SumsubSdk\Sumsub\Services\PortalDataService;

class ExportUsersToPortalJob implements ShouldQueue
{
    public function handle(PortalDataService $service)
    {
        $users = User::where('needs_portal_export', true)->get();

        foreach ($users as $user) {
            $data = $service->getUserData($user->referral_code, false);

            // Send to portal
            Http::post('https://portal.com/import', $data);

            // Mark as exported
            $user->update(['needs_portal_export' => false]);
        }
    }
}
```

## Limitations

### Data Not Available from Sumsub API

The following fields are not available via Sumsub API and will be `null`:

- `individual.occupation` - Not collected by Sumsub
- `individual.annual_income` - Not collected by Sumsub
- `document.number` - Not returned in API responses
- `document.expiry_date` - Not returned in API responses

If you need these fields, you must:
1. Collect them separately in your application
2. Store them in your database
3. Merge with Sumsub data before sending to portal

### Example: Merging Custom Data

```php
// Get Sumsub data
$sumsubData = $portalService->getUserData($referralCode, false);

// Get additional data from your database
$userData = User::where('referral_code', $referralCode)->first();

// Merge data
$sumsubData['individual']['occupation'] = $userData->occupation;
$sumsubData['individual']['annual_income'] = $userData->annual_income;
$sumsubData['document']['number'] = $userData->document_number;
$sumsubData['document']['expiry_date'] = $userData->document_expiry;

// Send to portal
return response()->json($sumsubData);
```

## Testing

```bash
# Test without images
curl http://your-app.test/api/portal/export/USER123

# Test with images
curl http://your-app.test/api/portal/export/USER123?include_images=true

# Test bulk export
curl -X POST http://your-app.test/api/portal/export/bulk \
  -H "Content-Type: application/json" \
  -d '{"referral_codes":["USER123","USER456"],"include_images":false}'

# Download as file
curl -O http://your-app.test/api/portal/download/USER123
```

## Support

- Main Documentation: `/README.md`
- API Reference: `/API_REFERENCE.md`
- Laravel Setup: `/LARAVEL_SETUP.md`

