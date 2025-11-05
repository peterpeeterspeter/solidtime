<?php

declare(strict_types=1);

namespace Tests\Unit\Model;

use App\Models\Webhook;
use App\Models\WebhookDelivery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

#[CoversClass(WebhookDelivery::class)]
class WebhookDeliveryModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_delivery_belongs_to_webhook(): void
    {
        // Arrange
        $webhook = Webhook::factory()->create();
        $delivery = WebhookDelivery::factory()->forWebhook($webhook)->create();

        // Act
        $deliveryWebhook = $delivery->webhook;

        // Assert
        $this->assertInstanceOf(Webhook::class, $deliveryWebhook);
        $this->assertTrue($deliveryWebhook->is($webhook));
    }

    public function test_webhook_delivery_does_not_have_updated_at(): void
    {
        // Arrange
        $delivery = WebhookDelivery::factory()->create();

        // Assert
        $this->assertNull(WebhookDelivery::UPDATED_AT);
        $this->assertArrayNotHasKey('updated_at', $delivery->toArray());
    }

    public function test_webhook_delivery_was_successful_returns_true_for_2xx(): void
    {
        // Arrange
        $delivery = WebhookDelivery::factory()->successful()->create();

        // Assert
        $this->assertTrue($delivery->wasSuccessful());
    }

    public function test_webhook_delivery_was_successful_returns_false_for_4xx(): void
    {
        // Arrange
        $delivery = WebhookDelivery::factory()->failed()->create();

        // Assert
        $this->assertFalse($delivery->wasSuccessful());
    }

    public function test_webhook_delivery_was_successful_returns_false_for_5xx(): void
    {
        // Arrange
        $delivery = WebhookDelivery::factory()->serverError()->create();

        // Assert
        $this->assertFalse($delivery->wasSuccessful());
    }

    public function test_webhook_delivery_was_successful_returns_false_for_network_error(): void
    {
        // Arrange
        $delivery = WebhookDelivery::factory()->networkError()->create();

        // Assert
        $this->assertFalse($delivery->wasSuccessful());
    }

    public function test_webhook_delivery_failed_returns_opposite_of_successful(): void
    {
        // Arrange
        $successfulDelivery = WebhookDelivery::factory()->successful()->create();
        $failedDelivery = WebhookDelivery::factory()->failed()->create();

        // Assert
        $this->assertFalse($successfulDelivery->failed());
        $this->assertTrue($failedDelivery->failed());
    }

    public function test_webhook_delivery_is_final_attempt_checks_max_retries(): void
    {
        // Arrange
        $delivery1 = WebhookDelivery::factory()->attempt(1)->create();
        $delivery5 = WebhookDelivery::factory()->attempt(5)->create();

        // Assert
        $this->assertFalse($delivery1->isFinalAttempt());
        $this->assertTrue($delivery5->isFinalAttempt());
    }

    public function test_webhook_delivery_get_retry_delay_seconds_returns_exponential_backoff(): void
    {
        // Arrange
        $delivery1 = WebhookDelivery::factory()->attempt(1)->create();
        $delivery2 = WebhookDelivery::factory()->attempt(2)->create();
        $delivery3 = WebhookDelivery::factory()->attempt(3)->create();
        $delivery4 = WebhookDelivery::factory()->attempt(4)->create();
        $delivery5 = WebhookDelivery::factory()->attempt(5)->create();

        // Assert
        $this->assertSame(2, $delivery1->getRetryDelaySeconds());
        $this->assertSame(4, $delivery2->getRetryDelaySeconds());
        $this->assertSame(8, $delivery3->getRetryDelaySeconds());
        $this->assertSame(16, $delivery4->getRetryDelaySeconds());
        $this->assertSame(32, $delivery5->getRetryDelaySeconds());
    }

    public function test_webhook_delivery_scope_successful_filters_2xx_status(): void
    {
        // Arrange
        WebhookDelivery::factory()->successful()->create();
        WebhookDelivery::factory()->failed()->create();
        WebhookDelivery::factory()->serverError()->create();

        // Act
        $successful = WebhookDelivery::successful()->get();

        // Assert
        $this->assertCount(1, $successful);
        $this->assertTrue($successful->first()->wasSuccessful());
    }

    public function test_webhook_delivery_scope_failed_filters_non_2xx_status(): void
    {
        // Arrange
        WebhookDelivery::factory()->successful()->create();
        WebhookDelivery::factory()->failed()->create();
        WebhookDelivery::factory()->serverError()->create();
        WebhookDelivery::factory()->networkError()->create();

        // Act
        $failed = WebhookDelivery::failed()->get();

        // Assert
        $this->assertCount(3, $failed);
    }

    public function test_webhook_delivery_scope_for_event_filters_by_event_type(): void
    {
        // Arrange
        WebhookDelivery::factory()->forEvent('time_entry.created')->create();
        WebhookDelivery::factory()->forEvent('invoice.sent')->create();
        WebhookDelivery::factory()->forEvent('time_entry.created')->create();

        // Act
        $timeEntryDeliveries = WebhookDelivery::forEvent('time_entry.created')->get();

        // Assert
        $this->assertCount(2, $timeEntryDeliveries);
        $this->assertTrue($timeEntryDeliveries->every(fn ($d) => $d->event_type === 'time_entry.created'));
    }

    public function test_webhook_delivery_payload_is_cast_to_array(): void
    {
        // Arrange
        $payload = [
            'id' => 'test-123',
            'event' => 'time_entry.created',
            'data' => ['description' => 'Test'],
        ];

        // Act
        $delivery = WebhookDelivery::factory()->create(['payload' => $payload]);

        // Assert
        $this->assertIsArray($delivery->payload);
        $this->assertSame('test-123', $delivery->payload['id']);
        $this->assertSame('time_entry.created', $delivery->payload['event']);
    }
}
