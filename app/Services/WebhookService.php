<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Webhook;
use App\Models\WebhookDelivery;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * WebhookService
 *
 * Handles webhook delivery, retry logic, and signature generation.
 */
class WebhookService
{
    /**
     * Dispatch a webhook event to all subscribed webhooks.
     *
     * @param  string  $event  Event type (e.g., 'time_entry.created')
     * @param  array<string, mixed>  $payload  Event data
     */
    public function dispatch(string $event, array $payload): void
    {
        // Find all active webhooks subscribed to this event
        $webhooks = Webhook::where('is_active', true)
            ->whereJsonContains('events', $event)
            ->get();

        if ($webhooks->isEmpty()) {
            Log::debug("No webhooks subscribed to event: {$event}");

            return;
        }

        foreach ($webhooks as $webhook) {
            $this->sendWebhook($webhook, $event, $payload);
        }
    }

    /**
     * Send webhook to a specific endpoint with retry logic.
     *
     * @param  Webhook  $webhook  Webhook configuration
     * @param  string  $event  Event type
     * @param  array<string, mixed>  $payload  Event data
     * @param  int  $attempt  Current attempt number (1-5)
     */
    public function sendWebhook(Webhook $webhook, string $event, array $payload, int $attempt = 1): void
    {
        // Create webhook payload with metadata
        $webhookPayload = [
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'event' => $event,
            'timestamp' => now()->toIso8601String(),
            'data' => $payload,
        ];

        // Create delivery record
        $delivery = WebhookDelivery::create([
            'webhook_id' => $webhook->id,
            'event_type' => $event,
            'payload' => $webhookPayload,
            'attempt' => $attempt,
        ]);

        try {
            // Generate HMAC signature for verification
            $signature = $this->generateSignature($webhook->secret, $webhookPayload);

            // Get timeout from config
            $timeout = config('rate-limiting.webhooks.timeout_seconds', 10);

            // Send HTTP POST request
            $response = Http::timeout($timeout)
                ->withHeaders([
                    'X-Timeclocker-Event' => $event,
                    'X-Timeclocker-Signature' => 'sha256='.$signature,
                    'X-Timeclocker-Delivery-ID' => $delivery->id,
                    'User-Agent' => 'Timeclocker-Webhooks/1.0',
                ])
                ->post($webhook->url, $webhookPayload);

            // Update delivery record with response
            $delivery->update([
                'response_status' => $response->status(),
                'response_body' => substr($response->body(), 0, 5000), // Limit to 5KB
                'delivered_at' => now(),
            ]);

            // Check if delivery was successful
            if ($response->successful()) {
                $webhook->incrementSuccessCount();
                Log::info("Webhook delivered successfully", [
                    'webhook_id' => $webhook->id,
                    'event' => $event,
                    'delivery_id' => $delivery->id,
                    'attempt' => $attempt,
                ]);
            } else {
                // Delivery failed, schedule retry if not final attempt
                $this->handleFailedDelivery($webhook, $delivery, $event, $payload, $attempt);
            }
        } catch (\Exception $e) {
            // Network error or other exception
            $delivery->update([
                'response_status' => 0,
                'error_message' => substr($e->getMessage(), 0, 1000),
                'delivered_at' => now(),
            ]);

            $this->handleFailedDelivery($webhook, $delivery, $event, $payload, $attempt);

            Log::error('Webhook delivery exception', [
                'webhook_id' => $webhook->id,
                'event' => $event,
                'delivery_id' => $delivery->id,
                'error' => $e->getMessage(),
                'attempt' => $attempt,
            ]);
        }
    }

    /**
     * Handle failed webhook delivery with retry logic.
     */
    protected function handleFailedDelivery(
        Webhook $webhook,
        WebhookDelivery $delivery,
        string $event,
        array $payload,
        int $attempt
    ): void {
        $webhook->incrementFailureCount();

        // Check if we should disable webhook due to too many failures
        $webhook->disableIfTooManyFailures(50);

        // Get max retries from config
        $maxRetries = config('rate-limiting.webhooks.max_retries', 5);

        // Retry if not final attempt
        if ($attempt < $maxRetries) {
            $delay = $delivery->getRetryDelaySeconds();

            Log::warning("Webhook delivery failed, scheduling retry", [
                'webhook_id' => $webhook->id,
                'event' => $event,
                'delivery_id' => $delivery->id,
                'attempt' => $attempt,
                'next_attempt' => $attempt + 1,
                'retry_in_seconds' => $delay,
            ]);

            // Schedule retry using Laravel's delayed dispatch
            dispatch(function () use ($webhook, $event, $payload, $attempt) {
                $this->sendWebhook($webhook, $event, $payload, $attempt + 1);
            })->delay(now()->addSeconds($delay));
        } else {
            Log::error('Webhook delivery failed after max retries', [
                'webhook_id' => $webhook->id,
                'event' => $event,
                'delivery_id' => $delivery->id,
                'max_attempts' => $maxRetries,
            ]);
        }
    }

    /**
     * Generate HMAC-SHA256 signature for webhook verification.
     *
     * @param  string  $secret  Webhook secret key
     * @param  array<string, mixed>  $payload  Webhook payload
     * @return string Hexadecimal signature
     */
    public function generateSignature(string $secret, array $payload): string
    {
        $payloadJson = json_encode($payload, JSON_UNESCAPED_SLASHES);

        return hash_hmac('sha256', $payloadJson, $secret);
    }

    /**
     * Verify webhook signature (for testing/validation).
     *
     * @param  string  $signature  Received signature
     * @param  string  $secret  Webhook secret
     * @param  array<string, mixed>  $payload  Webhook payload
     * @return bool True if signature is valid
     */
    public function verifySignature(string $signature, string $secret, array $payload): bool
    {
        // Remove 'sha256=' prefix if present
        $signature = str_replace('sha256=', '', $signature);

        $expectedSignature = $this->generateSignature($secret, $payload);

        // Use hash_equals for constant-time comparison (prevents timing attacks)
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Send test webhook to verify endpoint is working.
     *
     * @return array{success: bool, status: int|null, message: string}
     */
    public function sendTestWebhook(Webhook $webhook): array
    {
        $testPayload = [
            'id' => 'test',
            'description' => 'This is a test webhook from Timeclocker',
            'timestamp' => now()->toIso8601String(),
        ];

        $this->sendWebhook($webhook, 'webhook.test', $testPayload);

        // Get the most recent delivery
        $delivery = $webhook->deliveries()->latest('created_at')->first();

        if ($delivery === null) {
            return [
                'success' => false,
                'status' => null,
                'message' => 'Failed to create webhook delivery',
            ];
        }

        return [
            'success' => $delivery->wasSuccessful(),
            'status' => $delivery->response_status,
            'message' => $delivery->wasSuccessful()
                ? 'Test webhook delivered successfully'
                : 'Test webhook delivery failed: '.$delivery->error_message,
        ];
    }
}
