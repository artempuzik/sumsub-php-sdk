# Event System Documentation

Complete guide to using the event-driven webhook handling system.

## Overview

The SDK provides a powerful event system that automatically dispatches events when webhooks are received. This allows you to subscribe to specific events and handle them cleanly.

## Quick Start

```php
use SumsubSdk\Sumsub\Webhooks\WebhookHandler;
use SumsubSdk\Sumsub\Events\ApplicantReviewed;

$handler = new WebhookHandler($secretKey);

// Subscribe to event
$handler->getDispatcher()->listen(
    ApplicantReviewed::class,
    function(ApplicantReviewed $event) {
        if ($event->isApproved()) {
            echo "User {$event->getExternalUserId()} approved!";
        }
    }
);

// Handle webhook - event will be dispatched automatically
$webhook = $handler->handleFromRequest($headers, $body);
```

## Available Events

### 1. ApplicantCreated

Fired when a new applicant is created.

```php
$dispatcher->listen(ApplicantCreated::class, function($event) {
    $userId = $event->getExternalUserId();
    // Initialize user verification process
});
```

### 2. ApplicantPending

Fired when applicant verification is submitted and pending review.

```php
$dispatcher->listen(ApplicantPending::class, function($event) {
    $userId = $event->getExternalUserId();
    // Update status to 'pending'
    // Send "verification in progress" notification
});
```

### 3. ApplicantReviewed ⭐

Fired when applicant verification is reviewed (most important event).

```php
$dispatcher->listen(ApplicantReviewed::class, function(ApplicantReviewed $event) {
    if ($event->isApproved()) {
        // Verification approved
    } elseif ($event->isRejected()) {
        // Verification rejected
    } elseif ($event->requiresReview()) {
        // Requires additional review
    }
});
```

**Methods:**
- `isApproved(): bool` - Check if GREEN
- `isRejected(): bool` - Check if RED
- `requiresReview(): bool` - Check if YELLOW

### 4. ApplicantOnHold

Fired when applicant is put on hold for additional review.

```php
$dispatcher->listen(ApplicantOnHold::class, function($event) {
    // Notify admins
    // Create support ticket
});
```

### 5. ApplicantPersonalInfoChanged

Fired when applicant's personal information is updated.

```php
$dispatcher->listen(ApplicantPersonalInfoChanged::class, function($event) {
    // Sync updated information to your database
});
```

### 6. ApplicantReset

Fired when applicant is reset and can start verification again.

```php
$dispatcher->listen(ApplicantReset::class, function($event) {
    // Reset verification status in database
});
```

### 7. ApplicantActionPending

Fired when a specific action is pending.

```php
$dispatcher->listen(ApplicantActionPending::class, function($event) {
    // Handle action pending
});
```

### 8. ApplicantActionReviewed

Fired when a specific action is reviewed.

```php
$dispatcher->listen(ApplicantActionReviewed::class, function($event) {
    if ($event->isApproved()) {
        // Action approved
    }
});
```

**Methods:**
- `isApproved(): bool`
- `isRejected(): bool`

### 9. ApplicantActionOnHold

Fired when a specific action is put on hold.

```php
$dispatcher->listen(ApplicantActionOnHold::class, function($event) {
    // Handle action on hold
});
```

### 10. ApplicantWorkflowCompleted ⭐

Fired when the entire verification workflow is completed.

```php
$dispatcher->listen(ApplicantWorkflowCompleted::class, function($event) {
    // Perform final actions
    // Log completion
    // Trigger post-verification workflows
});
```

### 11. VideoIdentStatusChanged

Fired when video identification status changes.

```php
$dispatcher->listen(VideoIdentStatusChanged::class, function($event) {
    // Handle video ident status change
});
```

## Base Event Methods

All events extend the base `Event` class and provide these methods:

```php
$event->getApplicantId(): string        // Sumsub applicant ID
$event->getExternalUserId(): string     // Your internal user ID
$event->getWebhookData(): WebhookData   // Full webhook data
```

## EventDispatcher Methods

### listen()

Subscribe to an event.

```php
$dispatcher->listen(ApplicantReviewed::class, $callback);
```

### dispatch()

Manually dispatch an event.

```php
$event = new ApplicantReviewed($webhookData);
$dispatcher->dispatch($event);
```

### hasListeners()

Check if event has any listeners.

```php
if ($dispatcher->hasListeners(ApplicantReviewed::class)) {
    // Event has listeners
}
```

### forget()

Remove all listeners for an event.

```php
$dispatcher->forget(ApplicantReviewed::class);
```

### forgetAll()

Remove all listeners.

```php
$dispatcher->forgetAll();
```

### getListeners()

Get all listeners for an event.

```php
$listeners = $dispatcher->getListeners(ApplicantReviewed::class);
```

## Multiple Listeners

You can register multiple listeners for the same event:

```php
// Listener 1: Update database
$dispatcher->listen(ApplicantReviewed::class, function($event) {
    if ($event->isApproved()) {
        User::where('external_id', $event->getExternalUserId())
            ->update(['verified' => true]);
    }
});

// Listener 2: Send notification
$dispatcher->listen(ApplicantReviewed::class, function($event) {
    if ($event->isApproved()) {
        Mail::to($user)->send(new VerificationApprovedMail());
    }
});

// Listener 3: Log event
$dispatcher->listen(ApplicantReviewed::class, function($event) {
    Log::info('Verification reviewed', [
        'user' => $event->getExternalUserId(),
        'approved' => $event->isApproved()
    ]);
});
```

All listeners will be called in the order they were registered.

## Laravel Integration

### Using Service Provider

```php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use SumsubSdk\Sumsub\Webhooks\WebhookHandler;
use SumsubSdk\Sumsub\Events\ApplicantReviewed;
use App\Listeners\Sumsub\HandleApplicantReviewed;

class SumsubEventServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $handler = $this->app->make(WebhookHandler::class);
        $dispatcher = $handler->getDispatcher();

        // Register event listeners
        $dispatcher->listen(
            ApplicantReviewed::class,
            fn($event) => app(HandleApplicantReviewed::class)->handle($event)
        );
    }
}
```

### Using Listener Classes

```php
namespace App\Listeners\Sumsub;

use SumsubSdk\Sumsub\Events\ApplicantReviewed;
use App\Models\User;
use App\Notifications\VerificationApproved;

class HandleApplicantReviewed
{
    public function handle(ApplicantReviewed $event): void
    {
        $user = User::where('referral_code', $event->getExternalUserId())->first();

        if (!$user) {
            \Log::warning('User not found for webhook', [
                'external_user_id' => $event->getExternalUserId()
            ]);
            return;
        }

        if ($event->isApproved()) {
            $user->update([
                'verification_status' => 'verified',
                'kyc_verified_at' => now(),
                'sumsub_applicant_id' => $event->getApplicantId(),
            ]);

            $user->notify(new VerificationApproved());
        }
    }
}
```

## Advanced Usage

### Conditional Listeners

```php
$dispatcher->listen(ApplicantReviewed::class, function($event) {
    $webhook = $event->getWebhookData();

    // Only handle specific level
    if ($webhook->rawPayload['levelName'] === 'basic-kyc-level') {
        // Handle basic KYC
    }

    // Check correlation ID for debugging
    if ($webhook->correlationId) {
        Log::debug('Processing webhook', [
            'correlation_id' => $webhook->correlationId
        ]);
    }
});
```

### Stopping Event Propagation

If you want to stop calling subsequent listeners (not built-in, needs custom implementation):

```php
// Custom implementation example
class StoppableEventDispatcher extends EventDispatcher
{
    public function dispatch(Event $event): void
    {
        $eventClass = get_class($event);

        if (!isset($this->listeners[$eventClass])) {
            return;
        }

        foreach ($this->listeners[$eventClass] as $listener) {
            $result = $listener($event);

            // Stop if listener returns false
            if ($result === false) {
                break;
            }
        }
    }
}
```

## Testing Events

### Unit Testing

```php
use PHPUnit\Framework\TestCase;
use SumsubSdk\Sumsub\Events\EventDispatcher;
use SumsubSdk\Sumsub\Events\ApplicantReviewed;
use SumsubSdk\Sumsub\Webhooks\WebhookData;
use SumsubSdk\Sumsub\Enums\WebhookType;

class EventTest extends TestCase
{
    public function testEventIsDispatched()
    {
        $dispatcher = new EventDispatcher();
        $wasCalled = false;

        $dispatcher->listen(ApplicantReviewed::class, function() use (&$wasCalled) {
            $wasCalled = true;
        });

        $webhookData = new WebhookData(
            type: WebhookType::APPLICANT_REVIEWED,
            applicantId: 'test-id',
            externalUserId: 'user-123'
        );

        $event = new ApplicantReviewed($webhookData);
        $dispatcher->dispatch($event);

        $this->assertTrue($wasCalled);
    }
}
```

### Integration Testing

```php
public function testWebhookDispatchesEvent()
{
    $handler = new WebhookHandler($secretKey);
    $dispatcher = $handler->getDispatcher();

    $eventData = null;

    $dispatcher->listen(ApplicantReviewed::class, function($event) use (&$eventData) {
        $eventData = [
            'user_id' => $event->getExternalUserId(),
            'approved' => $event->isApproved()
        ];
    });

    $payload = json_encode([
        'type' => 'applicantReviewed',
        'applicantId' => 'test-id',
        'externalUserId' => 'user-123',
        'reviewResult' => ['reviewAnswer' => 'GREEN']
    ]);

    $signature = hash_hmac('sha256', $payload, $secretKey);

    $handler->handle($payload, $signature);

    $this->assertNotNull($eventData);
    $this->assertEquals('user-123', $eventData['user_id']);
    $this->assertTrue($eventData['approved']);
}
```

## Best Practices

### 1. Separate Concerns

Use different listeners for different responsibilities:

```php
// Database updates
$dispatcher->listen(ApplicantReviewed::class, $databaseUpdater);

// Notifications
$dispatcher->listen(ApplicantReviewed::class, $notificationSender);

// Logging
$dispatcher->listen(ApplicantReviewed::class, $logger);
```

### 2. Error Handling

Wrap listener code in try-catch to prevent one listener from breaking others:

```php
$dispatcher->listen(ApplicantReviewed::class, function($event) {
    try {
        // Your logic here
    } catch (\Exception $e) {
        \Log::error('Event listener error', [
            'event' => get_class($event),
            'error' => $e->getMessage()
        ]);
    }
});
```

### 3. Use Listener Classes

For complex logic, use dedicated listener classes instead of closures:

```php
$dispatcher->listen(
    ApplicantReviewed::class,
    fn($event) => app(HandleApplicantReviewed::class)->handle($event)
);
```

### 4. Queue Long-Running Tasks

In Laravel, dispatch queued jobs for long-running tasks:

```php
$dispatcher->listen(ApplicantReviewed::class, function($event) {
    if ($event->isApproved()) {
        dispatch(new ProcessVerifiedUserJob($event->getExternalUserId()));
    }
});
```

## Event Flow

```
Webhook Received
    ↓
WebhookHandler::handle()
    ↓
Validate Signature
    ↓
Parse Payload
    ↓
Create WebhookData
    ↓
Create Event (based on type)
    ↓
Dispatch Event
    ↓
All Listeners Called
    ↓
Return WebhookData
```

## Common Patterns

### Pattern 1: Update Database

```php
$dispatcher->listen(ApplicantReviewed::class, function($event) {
    User::where('referral_code', $event->getExternalUserId())->update([
        'verification_status' => $event->isApproved() ? 'verified' : 'rejected',
        'verified_at' => $event->isApproved() ? now() : null,
    ]);
});
```

### Pattern 2: Send Notifications

```php
$dispatcher->listen(ApplicantReviewed::class, function($event) {
    $user = User::where('referral_code', $event->getExternalUserId())->first();

    if ($event->isApproved()) {
        $user->notify(new VerificationApproved());
    } else {
        $user->notify(new VerificationRejected());
    }
});
```

### Pattern 3: Logging

```php
$dispatcher->listen(ApplicantReviewed::class, function($event) {
    \Log::info('Verification reviewed', [
        'user_id' => $event->getExternalUserId(),
        'applicant_id' => $event->getApplicantId(),
        'approved' => $event->isApproved(),
        'timestamp' => now()
    ]);
});
```

### Pattern 4: Analytics

```php
$dispatcher->listen(ApplicantReviewed::class, function($event) {
    if ($event->isApproved()) {
        // Track conversion event
        Analytics::track('verification_approved', [
            'user_id' => $event->getExternalUserId()
        ]);
    }
});
```

## Examples

See the `/examples` directory for complete examples:
- `webhook-with-events.php` - Basic event usage
- `events-laravel.php` - Laravel integration patterns

## API Reference

### EventDispatcher

```php
class EventDispatcher
{
    public function listen(string $eventClass, callable $listener): void
    public function dispatch(Event $event): void
    public function hasListeners(string $eventClass): bool
    public function forget(string $eventClass): void
    public function forgetAll(): void
    public function getListeners(string $eventClass): array
}
```

### Event (Base Class)

```php
abstract class Event
{
    public function getApplicantId(): string
    public function getExternalUserId(): string
    public function getWebhookData(): WebhookData
}
```

## Tips

1. **Register listeners before handling webhooks**
2. **Keep listeners focused** - one responsibility per listener
3. **Handle errors gracefully** - don't let one listener break others
4. **Use queued jobs** for long-running tasks in Laravel
5. **Log important events** for debugging and auditing

