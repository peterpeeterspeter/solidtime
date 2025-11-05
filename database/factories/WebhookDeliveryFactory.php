<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Webhook;
use App\Models\WebhookDelivery;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WebhookDelivery>
 */
class WebhookDeliveryFactory extends Factory
{
    protected $model = WebhookDelivery::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'webhook_id' => Webhook::factory(),
            'event_type' => 'time_entry.created',
            'payload' => [
                'id' => $this->faker->uuid(),
                'event' => 'time_entry.created',
                'timestamp' => now()->toIso8601String(),
                'data' => [
                    'id' => $this->faker->uuid(),
                    'description' => 'Test time entry',
                    'start' => now()->subHours(2)->toIso8601String(),
                    'end' => now()->toIso8601String(),
                ],
            ],
            'attempt' => 1,
            'response_status' => 200,
            'response_body' => json_encode(['received' => true]),
            'delivered_at' => now(),
        ];
    }

    /**
     * Successful delivery.
     */
    public function successful(): static
    {
        return $this->state([
            'response_status' => 200,
            'response_body' => json_encode(['received' => true]),
            'error_message' => null,
            'delivered_at' => now(),
        ]);
    }

    /**
     * Failed delivery (4xx error).
     */
    public function failed(): static
    {
        return $this->state([
            'response_status' => 400,
            'response_body' => json_encode(['error' => 'Bad Request']),
            'error_message' => 'Invalid webhook payload',
            'delivered_at' => now(),
        ]);
    }

    /**
     * Failed delivery (5xx error).
     */
    public function serverError(): static
    {
        return $this->state([
            'response_status' => 500,
            'response_body' => json_encode(['error' => 'Internal Server Error']),
            'error_message' => 'Server error occurred',
            'delivered_at' => now(),
        ]);
    }

    /**
     * Network error (no response).
     */
    public function networkError(): static
    {
        return $this->state([
            'response_status' => 0,
            'response_body' => null,
            'error_message' => 'Connection timeout',
            'delivered_at' => now(),
        ]);
    }

    /**
     * Retry attempt.
     */
    public function attempt(int $attemptNumber): static
    {
        return $this->state([
            'attempt' => $attemptNumber,
        ]);
    }

    /**
     * Pending delivery (not yet delivered).
     */
    public function pending(): static
    {
        return $this->state([
            'response_status' => null,
            'response_body' => null,
            'error_message' => null,
            'delivered_at' => null,
        ]);
    }

    /**
     * For a specific event type.
     */
    public function forEvent(string $eventType): static
    {
        $data = match ($eventType) {
            'invoice.sent' => [
                'id' => $this->faker->uuid(),
                'invoice_number' => 'INV-2025-001',
                'amount' => 1500.00,
            ],
            'payment.received' => [
                'id' => $this->faker->uuid(),
                'amount' => 1500.00,
                'currency' => 'USD',
            ],
            'member.added' => [
                'id' => $this->faker->uuid(),
                'user_id' => $this->faker->uuid(),
                'role' => 'member',
            ],
            default => [
                'id' => $this->faker->uuid(),
                'description' => 'Test data',
            ],
        };

        return $this->state([
            'event_type' => $eventType,
            'payload' => [
                'id' => $this->faker->uuid(),
                'event' => $eventType,
                'timestamp' => now()->toIso8601String(),
                'data' => $data,
            ],
        ]);
    }

    /**
     * For a specific webhook.
     */
    public function forWebhook(Webhook $webhook): static
    {
        return $this->state([
            'webhook_id' => $webhook->id,
        ]);
    }
}
