<?php

require __DIR__ . '/../vendor/autoload.php';

use SumsubSdk\Sumsub\Webhooks\WebhookHandler;
use SumsubSdk\Sumsub\Events\EventDispatcher;
use SumsubSdk\Sumsub\Events\ApplicantReviewed;
use SumsubSdk\Sumsub\Events\ApplicantPending;
use SumsubSdk\Sumsub\Events\ApplicantWorkflowCompleted;
use SumsubSdk\Sumsub\Events\ApplicantOnHold;

// Initialize webhook handler
$handler = new WebhookHandler(getenv('SUMSUB_APP_SECRET'));

// Get event dispatcher
$dispatcher = $handler->getDispatcher();

// Subscribe to ApplicantReviewed event
$dispatcher->listen(ApplicantReviewed::class, function(ApplicantReviewed $event) {
    echo "✅ Applicant Reviewed Event\n";
    echo "External User ID: {$event->getExternalUserId()}\n";
    echo "Applicant ID: {$event->getApplicantId()}\n";

    if ($event->isApproved()) {
        echo "Status: APPROVED ✅\n";

        // Update user in database
        // User::where('referral_code', $event->getExternalUserId())
        //     ->update([
        //         'verification_status' => 'verified',
        //         'kyc_verified_at' => now(),
        //     ]);

        // Send notification email
        // Mail::to($user)->send(new VerificationApprovedMail());

    } elseif ($event->isRejected()) {
        echo "Status: REJECTED ❌\n";

        // Update user in database
        // User::where('referral_code', $event->getExternalUserId())
        //     ->update(['verification_status' => 'rejected']);

        // Send notification email
        // Mail::to($user)->send(new VerificationRejectedMail());
    }

    echo "\n";
});

// Subscribe to ApplicantPending event
$dispatcher->listen(ApplicantPending::class, function(ApplicantPending $event) {
    echo "⏳ Applicant Pending Event\n";
    echo "External User ID: {$event->getExternalUserId()}\n";

    // Update user status
    // User::where('referral_code', $event->getExternalUserId())
    //     ->update(['verification_status' => 'pending']);

    echo "\n";
});

// Subscribe to ApplicantWorkflowCompleted event
$dispatcher->listen(ApplicantWorkflowCompleted::class, function(ApplicantWorkflowCompleted $event) {
    echo "✅ Workflow Completed Event\n";
    echo "External User ID: {$event->getExternalUserId()}\n";

    // Perform final actions
    // - Log completion
    // - Trigger additional workflows
    // - Send notifications

    echo "\n";
});

// Subscribe to ApplicantOnHold event
$dispatcher->listen(ApplicantOnHold::class, function(ApplicantOnHold $event) {
    echo "⚠️ Applicant On Hold Event\n";
    echo "External User ID: {$event->getExternalUserId()}\n";

    // Notify admins
    // - Send Slack notification
    // - Create support ticket

    echo "\n";
});

// Handle webhook
try {
    $payload = file_get_contents('php://input');
    $headers = getallheaders();

    $webhook = $handler->handleFromRequest($headers, $payload);

    echo "Webhook processed successfully\n";
    echo "Type: {$webhook->type->value}\n";

    // Events are automatically dispatched
    // All subscribers will be called

    http_response_code(200);
    echo json_encode(['status' => 'ok']);

} catch (\Exception $e) {
    error_log("Webhook error: {$e->getMessage()}");
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

