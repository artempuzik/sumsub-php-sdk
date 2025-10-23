<?php

/**
 * Example: Laravel Event Listeners Integration
 *
 * This shows how to integrate Sumsub SDK events with Laravel's event system
 */

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use SumsubSdk\Sumsub\Webhooks\WebhookHandler;
use SumsubSdk\Sumsub\Events\ApplicantReviewed;
use SumsubSdk\Sumsub\Events\ApplicantPending;
use SumsubSdk\Sumsub\Events\ApplicantWorkflowCompleted;

class SumsubEventServiceProvider extends ServiceProvider
{
    public function boot()
    {
        /** @var WebhookHandler $handler */
        $handler = $this->app->make(WebhookHandler::class);
        $dispatcher = $handler->getDispatcher();

        // Subscribe to events
        $this->registerApplicantReviewedListener($dispatcher);
        $this->registerApplicantPendingListener($dispatcher);
        $this->registerWorkflowCompletedListener($dispatcher);
    }

    private function registerApplicantReviewedListener($dispatcher): void
    {
        $dispatcher->listen(ApplicantReviewed::class, function(ApplicantReviewed $event) {
            if ($event->isApproved()) {
                // Dispatch Laravel event
                \Event::dispatch(new \App\Events\UserVerificationApproved(
                    externalUserId: $event->getExternalUserId(),
                    applicantId: $event->getApplicantId()
                ));
            } elseif ($event->isRejected()) {
                // Dispatch Laravel event
                \Event::dispatch(new \App\Events\UserVerificationRejected(
                    externalUserId: $event->getExternalUserId(),
                    applicantId: $event->getApplicantId()
                ));
            }
        });
    }

    private function registerApplicantPendingListener($dispatcher): void
    {
        $dispatcher->listen(ApplicantPending::class, function(ApplicantPending $event) {
            // Update user status
            \App\Models\User::where('referral_code', $event->getExternalUserId())
                ->update(['verification_status' => 'pending']);
        });
    }

    private function registerWorkflowCompletedListener($dispatcher): void
    {
        $dispatcher->listen(ApplicantWorkflowCompleted::class, function(ApplicantWorkflowCompleted $event) {
            // Log completion
            \Log::info('Sumsub workflow completed', [
                'external_user_id' => $event->getExternalUserId(),
                'applicant_id' => $event->getApplicantId(),
            ]);
        });
    }
}


/**
 * Alternative: Using separate Listener classes
 */

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

        // Subscribe using listener classes
        $dispatcher->listen(
            ApplicantReviewed::class,
            fn($event) => app(HandleApplicantReviewed::class)->handle($event)
        );
    }
}

namespace App\Listeners\Sumsub;

use SumsubSdk\Sumsub\Events\ApplicantReviewed;
use App\Models\User;
use App\Notifications\VerificationApproved;
use App\Notifications\VerificationRejected;

class HandleApplicantReviewed
{
    public function handle(ApplicantReviewed $event): void
    {
        $user = User::where('referral_code', $event->getExternalUserId())->first();

        if (!$user) {
            \Log::warning('User not found for sumsub webhook', [
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

        } elseif ($event->isRejected()) {
            $user->update([
                'verification_status' => 'rejected',
                'sumsub_applicant_id' => $event->getApplicantId(),
            ]);

            $user->notify(new VerificationRejected());
        }
    }
}


/**
 * Controller example
 */

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use SumsubSdk\Sumsub\Webhooks\WebhookHandler;

class SumsubWebhookController extends Controller
{
    public function __construct(
        private WebhookHandler $handler
    ) {}

    public function webhook(Request $request)
    {
        try {
            // Process webhook and dispatch events automatically
            $webhook = $this->handler->handleFromRequest(
                headers: $request->headers->all(),
                body: $request->getContent()
            );

            // All event listeners will be called automatically

            return response()->json(['status' => 'ok']);

        } catch (\Exception $e) {
            \Log::error('Webhook error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['error' => 'Processing failed'], 500);
        }
    }
}

