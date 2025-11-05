<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use App\Models\Webhook;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Webhook>
 */
class WebhookFactory extends Factory
{
    protected $model = Webhook::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'url' => 'https://example.com/webhooks/timeclocker',
            'secret' => Str::random(64),
            'events' => ['time_entry.created'],
            'is_active' => true,
            'delivery_success_count' => 0,
            'delivery_failure_count' => 0,
        ];
    }

    /**
     * Webhook subscribed to all events.
     */
    public function subscribedToAllEvents(): static
    {
        return $this->state([
            'events' => Webhook::AVAILABLE_EVENTS,
        ]);
    }

    /**
     * Webhook subscribed to time entry events.
     */
    public function timeEntryEvents(): static
    {
        return $this->state([
            'events' => [
                'time_entry.created',
                'time_entry.updated',
                'time_entry.deleted',
            ],
        ]);
    }

    /**
     * Webhook subscribed to invoice events.
     */
    public function invoiceEvents(): static
    {
        return $this->state([
            'events' => [
                'invoice.created',
                'invoice.sent',
                'invoice.paid',
                'invoice.overdue',
            ],
        ]);
    }

    /**
     * Webhook subscribed to payment events.
     */
    public function paymentEvents(): static
    {
        return $this->state([
            'events' => [
                'payment.received',
                'payment.refunded',
                'payment.failed',
            ],
        ]);
    }

    /**
     * Webhook subscribed to team member events.
     */
    public function memberEvents(): static
    {
        return $this->state([
            'events' => [
                'member.added',
                'member.removed',
                'member.role_changed',
            ],
        ]);
    }

    /**
     * Inactive webhook.
     */
    public function inactive(): static
    {
        return $this->state([
            'is_active' => false,
        ]);
    }

    /**
     * Webhook with many successful deliveries.
     */
    public function withSuccessfulDeliveries(int $count = 10): static
    {
        return $this->state([
            'delivery_success_count' => $count,
            'last_delivery_at' => now(),
        ]);
    }

    /**
     * Webhook with many failed deliveries.
     */
    public function withFailedDeliveries(int $count = 5): static
    {
        return $this->state([
            'delivery_failure_count' => $count,
            'last_delivery_at' => now(),
        ]);
    }

    /**
     * Webhook for a specific user.
     */
    public function forUser(User $user): static
    {
        return $this->state([
            'user_id' => $user->id,
        ]);
    }
}
