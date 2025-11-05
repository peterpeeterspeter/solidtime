<?php

declare(strict_types=1);

namespace Tests\Unit\Model;

use App\Models\User;
use App\Models\Webhook;
use App\Models\WebhookDelivery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

#[CoversClass(Webhook::class)]
class WebhookModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_belongs_to_user(): void
    {
        // Arrange
        $user = User::factory()->create();
        $webhook = Webhook::factory()->forUser($user)->create();

        // Act
        $webhookUser = $webhook->user;

        // Assert
        $this->assertInstanceOf(User::class, $webhookUser);
        $this->assertTrue($webhookUser->is($user));
    }

    public function test_webhook_has_many_deliveries(): void
    {
        // Arrange
        $webhook = Webhook::factory()->create();
        WebhookDelivery::factory()->count(3)->forWebhook($webhook)->create();

        // Act
        $deliveries = $webhook->deliveries;

        // Assert
        $this->assertCount(3, $deliveries);
        $this->assertInstanceOf(WebhookDelivery::class, $deliveries->first());
    }

    public function test_webhook_secret_is_hidden_from_serialization(): void
    {
        // Arrange
        $webhook = Webhook::factory()->create(['secret' => 'super-secret-key']);

        // Act
        $array = $webhook->toArray();

        // Assert
        $this->assertArrayNotHasKey('secret', $array);
        $this->assertSame('super-secret-key', $webhook->secret);
    }

    public function test_webhook_is_subscribed_to_checks_event_subscription(): void
    {
        // Arrange
        $webhook = Webhook::factory()->create([
            'events' => ['time_entry.created', 'invoice.sent'],
        ]);

        // Assert
        $this->assertTrue($webhook->isSubscribedTo('time_entry.created'));
        $this->assertTrue($webhook->isSubscribedTo('invoice.sent'));
        $this->assertFalse($webhook->isSubscribedTo('payment.received'));
    }

    public function test_webhook_calculates_success_rate_correctly(): void
    {
        // Arrange
        $webhook = Webhook::factory()->create([
            'delivery_success_count' => 75,
            'delivery_failure_count' => 25,
        ]);

        // Act
        $successRate = $webhook->success_rate;

        // Assert
        $this->assertSame(75.0, $successRate);
    }

    public function test_webhook_success_rate_is_zero_when_no_deliveries(): void
    {
        // Arrange
        $webhook = Webhook::factory()->create([
            'delivery_success_count' => 0,
            'delivery_failure_count' => 0,
        ]);

        // Act
        $successRate = $webhook->success_rate;

        // Assert
        $this->assertSame(0.0, $successRate);
    }

    public function test_webhook_increment_success_count_updates_counters(): void
    {
        // Arrange
        $webhook = Webhook::factory()->create([
            'delivery_success_count' => 5,
        ]);

        // Act
        $webhook->incrementSuccessCount();

        // Assert
        $this->assertSame(6, $webhook->fresh()->delivery_success_count);
        $this->assertNotNull($webhook->fresh()->last_delivery_at);
    }

    public function test_webhook_increment_failure_count_updates_counters(): void
    {
        // Arrange
        $webhook = Webhook::factory()->create([
            'delivery_failure_count' => 3,
        ]);

        // Act
        $webhook->incrementFailureCount();

        // Assert
        $this->assertSame(4, $webhook->fresh()->delivery_failure_count);
        $this->assertNotNull($webhook->fresh()->last_delivery_at);
    }

    public function test_webhook_disables_after_threshold_failures(): void
    {
        // Arrange
        $webhook = Webhook::factory()->create([
            'delivery_failure_count' => 50,
            'is_active' => true,
        ]);

        // Act
        $webhook->disableIfTooManyFailures(50);

        // Assert
        $this->assertFalse($webhook->fresh()->is_active);
    }

    public function test_webhook_does_not_disable_below_threshold(): void
    {
        // Arrange
        $webhook = Webhook::factory()->create([
            'delivery_failure_count' => 49,
            'is_active' => true,
        ]);

        // Act
        $webhook->disableIfTooManyFailures(50);

        // Assert
        $this->assertTrue($webhook->fresh()->is_active);
    }

    public function test_webhook_factory_creates_valid_webhook(): void
    {
        // Act
        $webhook = Webhook::factory()->create();

        // Assert
        $this->assertDatabaseHas('webhooks', [
            'id' => $webhook->id,
            'is_active' => true,
        ]);
    }

    public function test_webhook_factory_can_create_inactive_webhook(): void
    {
        // Act
        $webhook = Webhook::factory()->inactive()->create();

        // Assert
        $this->assertFalse($webhook->is_active);
    }

    public function test_webhook_cascades_delete_to_deliveries(): void
    {
        // Arrange
        $webhook = Webhook::factory()->create();
        $delivery = WebhookDelivery::factory()->forWebhook($webhook)->create();

        // Act
        $webhook->delete();

        // Assert
        $this->assertDatabaseMissing('webhooks', ['id' => $webhook->id]);
        $this->assertDatabaseMissing('webhook_deliveries', ['id' => $delivery->id]);
    }
}
