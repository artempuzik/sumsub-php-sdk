# Required Variables

Required variables for working with Sumsub PHP SDK.

## Environment Variables (.env)

```env
# Required
SUMSUB_APP_TOKEN=your_app_token_here
SUMSUB_APP_SECRET=your_secret_key_here

# Optional
SUMSUB_ENABLED=true
SUMSUB_LEVEL_NAME=basic-kyc-level
SUMSUB_BASE_URL=https://api.sumsub.com
```

## Configuration File (config/sumsub.php)

```php
<?php

return [
    'enabled' => env('SUMSUB_ENABLED', true),
    'token' => env('SUMSUB_APP_TOKEN'),
    'secret' => env('SUMSUB_APP_SECRET'),
    'level_name' => env('SUMSUB_LEVEL_NAME', 'basic-kyc-level'),
    'base_url' => env('SUMSUB_BASE_URL', 'https://api.sumsub.com'),
];
```

## Required Parameters for Integration

### 1. For Client Initialization

```php
use SumsubSdk\Sumsub\Client\SumsubClient;

$client = new SumsubClient(
    appToken: 'your_app_token',      // Required
    secretKey: 'your_secret_key',     // Required
    baseUrl: 'https://api.sumsub.com' // Optional
);
```

### 2. For Verification Widget

**Minimum required:**
```php
$tokenData = $client->generateAccessToken(
    externalUserId: 'USER123',        // Required: referral_code or any unique ID
    levelName: 'basic-kyc-level'      // Required: verification level name
);
```

**With optional parameters:**
```html
<!-- URL format -->
/sumsub/verify/USER123?email=user@example.com&phone=+1234567890

<!-- Query Parameters -->
- referral_code: Required
- email: Optional (improves UX - pre-fills email field)
- phone: Optional (improves UX - pre-fills phone field)
```

### 3. For WebSDK Initialization

```javascript
launchWebSdk(
    accessToken,      // Required - from generateAccessToken()
    applicantEmail,   // Optional - pre-fills email field
    applicantPhone    // Optional - pre-fills phone field
);
```

### 4. For Webhook Handler

```php
use SumsubSdk\Sumsub\Webhooks\WebhookHandler;

$handler = new WebhookHandler(
    secretKey: 'your_secret_key'  // Required - same as client
);
```

## Where to Get These Values

### SUMSUB_APP_TOKEN & SUMSUB_APP_SECRET

1. Login to [Sumsub Dashboard](https://cockpit.sumsub.com/)
2. Go to **Settings** → **Developers** → **App Tokens**
3. Create new token or use existing one
4. Copy **App Token** → use as `SUMSUB_APP_TOKEN`
5. Copy **Secret Key** → use as `SUMSUB_APP_SECRET`

**Important:** Keep these credentials secure! Never expose them in frontend code.

### SUMSUB_LEVEL_NAME

Verification level defines which documents are required:

1. Go to **Dashboard** → **Verification Levels**
2. Create or select a level (e.g., `basic-kyc-level`)
3. Configure required documents (Passport, ID Card, etc.)
4. Use the level name in configuration

**Common levels:**
- `basic-kyc-level` - Basic KYC (ID + Selfie)
- `advanced-kyc-level` - Advanced KYC (ID + Proof of Address)
- `basic-kyb-level` - Business verification

## API Endpoint Examples

### Show Widget
```
GET /sumsub/verify/{referralCode}?email=user@example.com
```

### Generate Token
```bash
POST /api/sumsub/token
Content-Type: application/json

{
  "referral_code": "USER123",
  "email": "user@example.com",     # Optional
  "phone": "+1234567890"            # Optional
}
```

### Check Status
```
GET /api/sumsub/status/USER123
```

### Get Applicant Data
```
GET /api/sumsub/applicant/USER123
```

### Get Documents
```
GET /api/sumsub/documents/USER123
```

### Get Document Image
```
GET /api/sumsub/documents/USER123/image/12345
```

## Minimal Working Example

```php
<?php

// 1. Initialize client (required)
$client = new \SumsubSdk\Sumsub\Client\SumsubClient(
    appToken: env('SUMSUB_APP_TOKEN'),
    secretKey: env('SUMSUB_APP_SECRET')
);

// 2. Generate token for user (required)
$tokenData = $client->generateAccessToken(
    externalUserId: 'USER123',          // Your user's referral_code
    levelName: 'basic-kyc-level'
);

// 3. Pass token to view
return view('sumsub.widget', [
    'token' => $tokenData['token'],     // Required
    'referral_code' => 'USER123',       // Required
    'email' => 'user@example.com',      // Optional
    'phone' => '+1234567890'            // Optional
]);
```

## JavaScript Integration

```javascript
// Initialize WebSDK (minimum required)
launchWebSdk(
    '{{ $token }}',                      // Required - from backend
    '{{ $email ?? "" }}',                // Optional
    '{{ $phone ?? "" }}'                 // Optional
);
```

## Validation Rules

### Referral Code (External User ID)
- **Required**: Yes
- **Type**: String
- **Min length**: 3 characters
- **Format**: Alphanumeric, can include dashes/underscores
- **Examples**: `USER123`, `REF-ABC-123`, `user_abc_123`

### Email
- **Required**: No
- **Type**: String
- **Format**: Valid email address
- **Example**: `user@example.com`

### Phone
- **Required**: No
- **Type**: String
- **Format**: International format recommended
- **Example**: `+1234567890`

## Security Notes

1. **Never expose credentials in frontend code**
   ```javascript
   // ❌ BAD - Don't do this
   const apiToken = 'sbx:your_token_here';

   // ✅ GOOD - Get token from backend
   fetch('/api/sumsub/token', {...})
   ```

2. **Always validate webhook signatures**
   ```php
   $handler->handleFromRequest($headers, $body); // Validates automatically
   ```

3. **Use HTTPS in production**
   ```env
   SUMSUB_BASE_URL=https://api.sumsub.com  # Always HTTPS
   ```

4. **Rate limit your endpoints**
   ```php
   Route::middleware(['throttle:60,1'])->group(function () {
       // Sumsub routes
   });
   ```

## Testing

### Check if credentials are correct

```bash
php artisan tinker
```

```php
$client = app(\SumsubSdk\Sumsub\Client\SumsubClient::class);
$token = $client->generateAccessToken('test-user-123', 'basic-kyc-level');
dump($token); // Should return array with 'token' key
```

### Test verification widget

```
http://your-app.test/sumsub/verify/test-user-123?email=test@example.com
```

## Troubleshooting

### "Invalid credentials" error
- Check `SUMSUB_APP_TOKEN` and `SUMSUB_APP_SECRET` are correct
- Ensure no extra spaces in .env file

### "Level not found" error
- Check `SUMSUB_LEVEL_NAME` matches exactly with dashboard
- Level names are case-sensitive

### Widget doesn't load
- Check token is generated successfully
- Verify JavaScript console for errors
- Ensure CORS is configured correctly

### 403 Forbidden on document images
- Enable "Get Applicant Documents/Images" permission in API token settings
- Check token has correct permissions in Sumsub Dashboard

## Support

- SDK Documentation: `/README.md`
- Laravel Setup: `/LARAVEL_SETUP.md`
- API Reference: `/API_REFERENCE.md`
- Events Documentation: `/EVENTS.md`
- Sumsub Docs: https://docs.sumsub.com/

