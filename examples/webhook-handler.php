<?php

require __DIR__ . '/../vendor/autoload.php';

use SumsubSdk\Sumsub\Webhooks\WebhookHandler;
use SumsubSdk\Sumsub\Exceptions\ValidationException;
use SumsubSdk\Sumsub\Exceptions\WebhookException;

// Initialize webhook handler
$handler = new WebhookHandler(getenv('SUMSUB_APP_SECRET'));

// Get webhook data
$payload = file_get_contents('php://input');
$headers = getallheaders();

try {
    // Validate and parse webhook
    $webhook = $handler->handleFromRequest($headers, $payload);

    // Log webhook event
    error_log("Webhook received: {$webhook->type->value}");
    error_log("External User ID: {$webhook->externalUserId}");
    error_log("Applicant ID: {$webhook->applicantId}");

    // Handle different webhook types
    switch ($webhook->type) {
        case \SumsubSdk\Sumsub\Enums\WebhookType::APPLICANT_REVIEWED:
            if ($webhook->isApproved()) {
                // User was approved
                echo "User {$webhook->externalUserId} approved!\n";

                // Update your database
                // User::where('external_id', $webhook->externalUserId)
                //     ->update(['verification_status' => 'verified']);

            } elseif ($webhook->isRejected()) {
                // User was rejected
                echo "User {$webhook->externalUserId} rejected\n";
                $rejectReason = $webhook->reviewResult['moderationComment'] ?? 'Unknown';
                echo "Reason: {$rejectReason}\n";
            }
            break;

        case \SumsubSdk\Sumsub\Enums\WebhookType::APPLICANT_PENDING:
            echo "User {$webhook->externalUserId} verification pending\n";
            break;

        case \SumsubSdk\Sumsub\Enums\WebhookType::APPLICANT_WORKFLOW_COMPLETED:
            echo "Workflow completed for {$webhook->externalUserId}\n";
            break;
    }

    // Return success response
    http_response_code(200);
    echo json_encode(['status' => 'ok']);

} catch (ValidationException $e) {
    // Invalid signature or missing fields
    error_log("Webhook validation failed: {$e->getMessage()}");
    http_response_code(400);
    echo json_encode(['error' => 'Invalid webhook']);

} catch (WebhookException $e) {
    // Webhook processing error
    error_log("Webhook error: {$e->getMessage()}");
    http_response_code(500);
    echo json_encode(['error' => 'Processing failed']);
}

