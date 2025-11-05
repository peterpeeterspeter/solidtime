<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Webhook;
use App\Models\WebhookDelivery;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookDispatcher
{
    /**
     * Dispatch an event to all subscribed webhooks
     */
    public function dispatch(string $eventType, array $payload, string $organizationId): void
    {
        $webhooks = Webhook::forOrganization($organizationId)
            ->active()
            ->healthy()
            ->subscribedTo($eventType)
            ->get();

        foreach ($webhooks as $webhook) {
            $this->send($webhook, $eventType, $payload);
        }
    }

    /**
     * Send a webhook delivery
     */
    public function send(Webhook $webhook, string $eventType, array $payload): WebhookDelivery
    {
        $delivery = WebhookDelivery::create([
            'webhook_id' => $webhook->id,
            'event_type' => $eventType,
            'payload' => $payload,
            'delivery_id' => WebhookDelivery::generateDeliveryId(),
            'status' => 'pending',
            'attempted_at' => now(),
            'attempt_number' => 1,
            'max_attempts' => 3,
        ]);

        try {
            $startTime = microtime(true);

            $response = Http::timeout(10)
                ->withHeaders($this->buildHeaders($webhook, $delivery, $payload))
                ->post($webhook->url, [
                    'event' => $eventType,
                    'delivery_id' => $delivery->delivery_id,
                    'timestamp' => now()->toIso8601String(),
                    'data' => $payload,
                ]);

            $duration = (int) ((microtime(true) - $startTime) * 1000);

            if ($response->successful()) {
                $delivery->markAsSuccess(
                    $response->status(),
                    $response->body(),
                    $duration
                );

                $webhook->recordSuccess();
            } else {
                $delivery->markAsFailed(
                    "HTTP {$response->status()}: {$response->body()}",
                    $response->status(),
                    $response->body(),
                    $duration
                );

                $webhook->recordFailure("HTTP {$response->status()}");
            }
        } catch (\Exception $e) {
            $duration = (int) ((microtime(true) - $startTime) * 1000);

            $delivery->markAsFailed($e->getMessage(), null, null, $duration);
            $webhook->recordFailure($e->getMessage());

            Log::error("Webhook delivery failed", [
                'webhook_id' => $webhook->id,
                'delivery_id' => $delivery->delivery_id,
                'error' => $e->getMessage(),
            ]);
        }

        return $delivery;
    }

    /**
     * Retry a failed delivery
     */
    public function retry(WebhookDelivery $delivery): void
    {
        if (! $delivery->canRetry()) {
            return;
        }

        $webhook = $delivery->webhook;

        $delivery->incrementAttempt();

        try {
            $startTime = microtime(true);

            $response = Http::timeout(10)
                ->withHeaders($this->buildHeaders($webhook, $delivery, $delivery->payload))
                ->post($webhook->url, [
                    'event' => $delivery->event_type,
                    'delivery_id' => $delivery->delivery_id,
                    'timestamp' => now()->toIso8601String(),
                    'data' => $delivery->payload,
                    'retry_attempt' => $delivery->attempt_number,
                ]);

            $duration = (int) ((microtime(true) - $startTime) * 1000);

            if ($response->successful()) {
                $delivery->markAsSuccess($response->status(), $response->body(), $duration);
                $webhook->recordSuccess();
            } else {
                $delivery->markAsFailed(
                    "HTTP {$response->status()}",
                    $response->status(),
                    $response->body(),
                    $duration
                );
            }
        } catch (\Exception $e) {
            $duration = (int) ((microtime(true) - ($startTime ?? microtime(true))) * 1000);
            $delivery->markAsFailed($e->getMessage(), null, null, $duration);
        }
    }

    /**
     * Process retryable deliveries
     */
    public function processRetries(): int
    {
        $retryable = WebhookDelivery::retryable()->limit(100)->get();
        $processed = 0;

        foreach ($retryable as $delivery) {
            $this->retry($delivery);
            $processed++;
        }

        return $processed;
    }

    /**
     * Build webhook headers including signature
     */
    protected function buildHeaders(Webhook $webhook, WebhookDelivery $delivery, array $payload): array
    {
        $headers = [
            'Content-Type' => 'application/json',
            'User-Agent' => 'Solidtime-Webhooks/1.0',
            'X-Solidtime-Event' => $delivery->event_type,
            'X-Solidtime-Delivery' => $delivery->delivery_id,
            'X-Solidtime-Timestamp' => now()->timestamp,
        ];

        if ($webhook->secret) {
            $signature = $this->generateSignature($webhook->secret, $payload);
            $headers['X-Solidtime-Signature'] = $signature;
        }

        return $headers;
    }

    /**
     * Generate HMAC signature for webhook payload
     */
    protected function generateSignature(string $secret, array $payload): string
    {
        $jsonPayload = json_encode($payload);

        return hash_hmac('sha256', $jsonPayload, $secret);
    }
}
