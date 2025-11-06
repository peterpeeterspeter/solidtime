<?php

namespace Database\Factories;

use App\Models\WebhookDelivery;
use App\Models\Webhook;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class WebhookDeliveryFactory extends Factory
{
    protected $model = WebhookDelivery::class;

    public function definition(): array
    {
        return [
            'id' => Str::uuid(),
            'webhook_id' => Webhook::factory(),
            'delivery_id' => WebhookDelivery::generateDeliveryId(),
            'event_type' => $this->faker->randomElement([
                'time_entry.started',
                'time_entry.stopped',
                'project.created',
                'task.completed'
            ]),
            'payload' => [
                'event' => 'time_entry.started',
                'data' => [
                    'id' => Str::uuid(),
                    'description' => $this->faker->sentence(),
                ],
            ],
            'status' => 'pending',
            'attempt_number' => 1,
            'max_attempts' => 3,
            'http_status_code' => null,
            'response_body' => null,
            'error_message' => null,
            'duration_ms' => null,
            'next_retry_at' => null,
            'attempted_at' => now(),
            'completed_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function successful(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'success',
            'http_status_code' => 200,
            'response_body' => json_encode(['success' => true]),
            'duration_ms' => $this->faker->numberBetween(100, 2000),
            'completed_at' => now(),
        ]);
    }

    public function failed(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'http_status_code' => 500,
            'error_message' => 'Internal Server Error',
            'response_body' => json_encode(['error' => 'Server error']),
            'duration_ms' => $this->faker->numberBetween(5000, 10000),
            'completed_at' => now(),
        ]);
    }

    public function retrying(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'retrying',
            'attempt_number' => 2,
            'next_retry_at' => now()->addMinutes(10),
            'error_message' => 'Connection timeout',
        ]);
    }
}
