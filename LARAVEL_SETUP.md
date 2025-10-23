# Laravel Setup Guide

Complete guide for integrating Sumsub PHP SDK into Laravel application.

## Installation

### 1. Install the package

```bash
composer require artempuzik/sumsub-php-sdk
```

### 2. Configure environment variables

Add to your `.env` file:

```env
# Required
SUMSUB_APP_TOKEN=your_app_token_here
SUMSUB_APP_SECRET=your_secret_key_here

# Optional
SUMSUB_ENABLED=true
SUMSUB_LEVEL_NAME=basic-kyc-level
SUMSUB_BASE_URL=https://api.sumsub.com
```

### 3. Create configuration file

Create `config/sumsub.php`:

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

## Service Provider Setup

### 1. Create Service Provider

```bash
php artisan make:provider SumsubServiceProvider
```

### 2. Register SDK Client

Edit `app/Providers/SumsubServiceProvider.php`:

```php
<?php

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
                secretKey: config('sumsub.secret'),
                baseUrl: config('sumsub.base_url', 'https://api.sumsub.com')
            );
        });

        // Register Webhook Handler
        $this->app->singleton(WebhookHandler::class, function ($app) {
            return new WebhookHandler(
                secretKey: config('sumsub.secret')
            );
        });
    }

    public function boot()
    {
        // Register webhook event listeners
        if ($this->app->bound(WebhookHandler::class)) {
            $this->registerWebhookListeners();
        }
    }

    protected function registerWebhookListeners()
    {
        $handler = $this->app->make(WebhookHandler::class);
        $dispatcher = $handler->getDispatcher();

        // Listen to ApplicantReviewed event
        $dispatcher->listen(
            \SumsubSdk\Sumsub\Events\ApplicantReviewed::class,
            fn($event) => $this->handleApplicantReviewed($event)
        );

        // Listen to ApplicantWorkflowCompleted event
        $dispatcher->listen(
            \SumsubSdk\Sumsub\Events\ApplicantWorkflowCompleted::class,
            fn($event) => $this->handleWorkflowCompleted($event)
        );
    }

    protected function handleApplicantReviewed($event)
    {
        $user = \App\Models\User::where('referral_code', $event->getExternalUserId())->first();

        if ($user) {
            $user->update([
                'sumsub_applicant_id' => $event->getApplicantId(),
                'verification_status' => $event->isApproved() ? 'verified' :
                    ($event->isRejected() ? 'rejected' : 'pending'),
                'kyc_verified_at' => $event->isApproved() ? now() : null,
            ]);

            // Send notification
            if ($event->isApproved()) {
                $user->notify(new \App\Notifications\VerificationApproved());
            }
        }
    }

    protected function handleWorkflowCompleted($event)
    {
        \Log::info('Verification workflow completed', [
            'user_id' => $event->getExternalUserId(),
            'applicant_id' => $event->getApplicantId(),
        ]);
    }
}
```

### 3. Register Provider

Add to `config/app.php`:

```php
'providers' => [
    // ...
    App\Providers\SumsubServiceProvider::class,
],
```

## Controller Setup

### 1. Copy example controller

Copy from `examples/laravel/SumsubController.php` to your `app/Http/Controllers/` directory.

### 2. Add routes

Copy routes from `examples/laravel/routes.php` to your `routes/web.php` and `routes/api.php`.

### 3. Copy view

Copy `examples/laravel/views/widget.blade.php` to `resources/views/sumsub/widget.blade.php`.

## Database Migration

Create migration for storing verification data:

```bash
php artisan make:migration add_sumsub_fields_to_users_table
```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('sumsub_applicant_id')->nullable()->index();
            $table->string('verification_status')->default('not_started'); // not_started, pending, verified, rejected
            $table->timestamp('kyc_verified_at')->nullable();
            $table->text('verification_notes')->nullable();
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'sumsub_applicant_id',
                'verification_status',
                'kyc_verified_at',
                'verification_notes'
            ]);
        });
    }
};
```

Run migration:

```bash
php artisan migrate
```

## Webhook Controller

Create webhook controller:

```bash
php artisan make:controller WebhookController
```

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use SumsubSdk\Sumsub\Webhooks\WebhookHandler;
use SumsubSdk\Sumsub\Exceptions\ValidationException;

class WebhookController extends Controller
{
    protected WebhookHandler $handler;

    public function __construct(WebhookHandler $handler)
    {
        $this->handler = $handler;
    }

    /**
     * Handle Sumsub webhook
     *
     * Route: POST /webhooks/sumsub
     */
    public function sumsub(Request $request)
    {
        try {
            $webhook = $this->handler->handleFromRequest(
                headers: $request->headers->all(),
                body: $request->getContent()
            );

            // Events are dispatched automatically
            // See SumsubServiceProvider::registerWebhookListeners()

            \Log::info('Webhook processed', [
                'type' => $webhook->type->value,
                'applicant_id' => $webhook->applicantId,
                'external_user_id' => $webhook->externalUserId,
            ]);

            return response()->json(['status' => 'ok']);

        } catch (ValidationException $e) {
            \Log::warning('Invalid webhook signature', [
                'error' => $e->getMessage(),
                'ip' => $request->ip()
            ]);

            return response()->json(['error' => 'Invalid signature'], 400);

        } catch (\Exception $e) {
            \Log::error('Webhook processing error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json(['error' => 'Processing failed'], 500);
        }
    }
}
```

Add webhook route (without CSRF protection):

```php
// routes/api.php
Route::post('/webhooks/sumsub', [WebhookController::class, 'sumsub']);
```

Update `app/Http/Middleware/VerifyCsrfToken.php`:

```php
protected $except = [
    'webhooks/sumsub',
];
```

## Usage Examples

### 1. Show verification widget

```php
// In your blade template
<a href="{{ route('sumsub.verify') }}" class="btn btn-primary">
    Verify Your Identity
</a>
```

### 2. Check verification status

```javascript
// In your JavaScript
fetch('/api/sumsub/status')
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('Verification status:', data.data.verification_status);
            if (data.data.is_verified) {
                // User is verified
            }
        }
    });
```

### 3. Get applicant data in controller

```php
use SumsubSdk\Sumsub\Client\SumsubClient;

public function showProfile(SumsubClient $client)
{
    $user = auth()->user();

    try {
        $applicant = $client->getApplicantByExternalUserId($user->referral_code);
        $isVerified = $applicant->getData()->isVerified();

        return view('profile', compact('user', 'isVerified'));
    } catch (\Exception $e) {
        // User not verified yet
        return view('profile', ['user' => $user, 'isVerified' => false]);
    }
}
```

### 4. Get documents

```php
public function documents(SumsubClient $client)
{
    $user = auth()->user();
    $applicant = $client->getApplicantByExternalUserId($user->referral_code);
    $documents = $client->getDocuments($applicant->getData()->id);

    return view('documents', [
        'documents' => $documents->toArray(),
        'total' => $documents->count(),
        'approved' => $documents->getApproved()->count()
    ]);
}
```

## Testing

### 1. Test token generation

```bash
php artisan tinker
```

```php
$client = app(\SumsubSdk\Sumsub\Client\SumsubClient::class);
$token = $client->generateAccessToken('test-user-123', 'basic-kyc-level');
dd($token);
```

### 2. Test webhook locally

Use ngrok to expose your local server:

```bash
ngrok http 8000
```

Then configure webhook URL in Sumsub dashboard:
```
https://your-ngrok-url.ngrok.io/webhooks/sumsub
```

### 3. Unit test example

```php
namespace Tests\Feature;

use Tests\TestCase;
use SumsubSdk\Sumsub\Client\SumsubClient;

class SumsubTest extends TestCase
{
    public function test_can_generate_access_token()
    {
        $client = app(SumsubClient::class);

        $tokenData = $client->generateAccessToken('test-user-123', 'basic-kyc-level');

        $this->assertArrayHasKey('token', $tokenData);
        $this->assertNotEmpty($tokenData['token']);
    }

    public function test_can_get_applicant()
    {
        $client = app(SumsubClient::class);

        $applicant = $client->getApplicantByExternalUserId('existing-user-id');

        $this->assertInstanceOf(\SumsubSdk\Sumsub\Resources\ApplicantResource::class, $applicant);
        $this->assertEquals('existing-user-id', $applicant->getData()->externalUserId);
    }
}
```

## Required Environment Variables Summary

| Variable | Required | Description | Example |
|----------|----------|-------------|---------|
| `SUMSUB_APP_TOKEN` | **Yes** | Your Sumsub application token | `sbx:XXXXXX...` |
| `SUMSUB_APP_SECRET` | **Yes** | Your Sumsub secret key | `XXXXXXXX...` |
| `SUMSUB_ENABLED` | No | Enable/disable Sumsub | `true` |
| `SUMSUB_LEVEL_NAME` | No | Verification level name | `basic-kyc-level` |
| `SUMSUB_BASE_URL` | No | API base URL | `https://api.sumsub.com` |

## Troubleshooting

### Issue: Token generation fails

**Solution:** Check that your `SUMSUB_APP_TOKEN` and `SUMSUB_APP_SECRET` are correct.

### Issue: Webhook signature validation fails

**Solution:** Ensure your webhook endpoint is public and CSRF protection is disabled for that route.

### Issue: User not found (404)

**Solution:** User hasn't started verification yet. This is normal for new users.

### Issue: 403 Forbidden when getting documents

**Solution:** Check API token permissions in Sumsub Dashboard. Enable "Get Applicant Documents/Images" permission.

## Security Recommendations

1. **Never expose your secret key** in frontend code
2. **Always validate webhook signatures**
3. **Use HTTPS** for all API requests
4. **Implement rate limiting** on verification endpoints
5. **Log all verification events** for audit trail
6. **Set up proper CORS** if using API from frontend

## Support

- SDK Documentation: `/README.md`
- Events Documentation: `/EVENTS.md`
- API Reference: `/API_REFERENCE.md`
- Integration Guide: `/INTEGRATION.md`
- Sumsub Docs: https://docs.sumsub.com/

