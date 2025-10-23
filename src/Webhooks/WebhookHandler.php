<?php

declare(strict_types=1);

namespace SumsubSdk\Sumsub\Webhooks;

use SumsubSdk\Sumsub\Validators\WebhookValidator;
use SumsubSdk\Sumsub\Exceptions\WebhookException;
use SumsubSdk\Sumsub\Exceptions\ValidationException;
use SumsubSdk\Sumsub\Events\EventDispatcher;
use SumsubSdk\Sumsub\Events\Event;
use SumsubSdk\Sumsub\Events\ApplicantCreated;
use SumsubSdk\Sumsub\Events\ApplicantPending;
use SumsubSdk\Sumsub\Events\ApplicantReviewed;
use SumsubSdk\Sumsub\Events\ApplicantOnHold;
use SumsubSdk\Sumsub\Events\ApplicantPersonalInfoChanged;
use SumsubSdk\Sumsub\Events\ApplicantReset;
use SumsubSdk\Sumsub\Events\ApplicantActionPending;
use SumsubSdk\Sumsub\Events\ApplicantActionReviewed;
use SumsubSdk\Sumsub\Events\ApplicantActionOnHold;
use SumsubSdk\Sumsub\Events\ApplicantWorkflowCompleted;
use SumsubSdk\Sumsub\Events\VideoIdentStatusChanged;
use SumsubSdk\Sumsub\Enums\WebhookType;

/**
 * Handler for Sumsub webhooks
 */
class WebhookHandler
{
    private ?EventDispatcher $dispatcher = null;

    public function __construct(
        private string $secretKey
    ) {}

    /**
     * Set event dispatcher
     */
    public function setDispatcher(EventDispatcher $dispatcher): self
    {
        $this->dispatcher = $dispatcher;
        return $this;
    }

    /**
     * Get event dispatcher
     */
    public function getDispatcher(): EventDispatcher
    {
        if (!$this->dispatcher) {
            $this->dispatcher = new EventDispatcher();
        }

        return $this->dispatcher;
    }

    /**
     * Handle incoming webhook
     *
     * @throws WebhookException
     * @throws ValidationException
     */
    public function handle(
        string $payload,
        string $signature,
        string $algorithm = 'sha256'
    ): WebhookData {
        $validator = new WebhookValidator($this->secretKey);

        // Validate signature
        $validator->validateSignature($payload, $signature, $algorithm);

        // Parse payload
        $data = json_decode($payload, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new WebhookException('Invalid JSON payload: ' . json_last_error_msg());
        }

        // Validate payload structure
        $validator->validatePayload($data);

        // Create webhook data object
        $webhookData = WebhookData::fromArray($data);

        // Dispatch event
        $this->dispatchEvent($webhookData);

        return $webhookData;
    }

    /**
     * Dispatch event based on webhook type
     */
    private function dispatchEvent(WebhookData $webhookData): void
    {
        $event = $this->createEvent($webhookData);

        if ($event) {
            $this->getDispatcher()->dispatch($event);
        }
    }

    /**
     * Create event instance based on webhook type
     */
    private function createEvent(WebhookData $webhookData): ?Event
    {
        return match($webhookData->type) {
            WebhookType::APPLICANT_CREATED => new ApplicantCreated($webhookData),
            WebhookType::APPLICANT_PENDING => new ApplicantPending($webhookData),
            WebhookType::APPLICANT_REVIEWED => new ApplicantReviewed($webhookData),
            WebhookType::APPLICANT_ON_HOLD => new ApplicantOnHold($webhookData),
            WebhookType::APPLICANT_PERSONAL_INFO_CHANGED => new ApplicantPersonalInfoChanged($webhookData),
            WebhookType::APPLICANT_RESET => new ApplicantReset($webhookData),
            WebhookType::APPLICANT_ACTION_PENDING => new ApplicantActionPending($webhookData),
            WebhookType::APPLICANT_ACTION_REVIEWED => new ApplicantActionReviewed($webhookData),
            WebhookType::APPLICANT_ACTION_ON_HOLD => new ApplicantActionOnHold($webhookData),
            WebhookType::APPLICANT_WORKFLOW_COMPLETED => new ApplicantWorkflowCompleted($webhookData),
            WebhookType::VIDEO_IDENT_STATUS_CHANGED => new VideoIdentStatusChanged($webhookData),
            default => null,
        };
    }

    /**
     * Handle webhook from HTTP request
     *
     * @param array $headers HTTP headers
     * @param string $body Request body
     * @throws WebhookException
     * @throws ValidationException
     */
    public function handleFromRequest(array $headers, string $body): WebhookData
    {
        // Get signature from headers
        $signature = $this->extractSignature($headers);
        $algorithm = $this->extractAlgorithm($headers);

        return $this->handle($body, $signature, $algorithm);
    }

    private function extractSignature(array $headers): string
    {
        $headerName = $this->findHeader($headers, 'x-payload-digest');

        if (!$headerName || empty($headers[$headerName])) {
            throw new WebhookException('Missing x-payload-digest header');
        }

        return is_array($headers[$headerName])
            ? $headers[$headerName][0]
            : $headers[$headerName];
    }

    private function extractAlgorithm(array $headers): string
    {
        $headerName = $this->findHeader($headers, 'x-payload-digest-alg');

        if (!$headerName || empty($headers[$headerName])) {
            return 'sha256'; // Default
        }

        $algoStr = is_array($headers[$headerName])
            ? $headers[$headerName][0]
            : $headers[$headerName];

        return match($algoStr) {
            'HMAC_SHA1_HEX' => 'sha1',
            'HMAC_SHA256_HEX' => 'sha256',
            'HMAC_SHA512_HEX' => 'sha512',
            default => 'sha256',
        };
    }

    private function findHeader(array $headers, string $name): ?string
    {
        $name = strtolower($name);

        foreach ($headers as $key => $value) {
            if (strtolower($key) === $name) {
                return $key;
            }
        }

        return null;
    }
}

