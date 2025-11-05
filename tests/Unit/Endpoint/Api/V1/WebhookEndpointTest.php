<?php

declare(strict_types=1);

namespace Tests\Unit\Endpoint\Api\V1;

use App\Http\Controllers\Api\V1\WebhookController;
use App\Models\User;
use App\Models\Webhook;
use App\Models\WebhookDelivery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Passport\Passport;
use Tests\TestCase;

#[CoversClass(WebhookController::class)]
class WebhookEndpointTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_index_returns_all_webhooks_for_user(): void
    {
        // Arrange
        Passport::actingAs($this->user);
        $webhook1 = Webhook::factory()->forUser($this->user)->create();
        $webhook2 = Webhook::factory()->forUser($this->user)->create();
        Webhook::factory()->create(); // Other user's webhook

        // Act
        $response = $this->getJson(route('api.v1.webhooks.index'));

        // Assert
        $response->assertOk();
        $response->assertJsonCount(2, 'data');
        $response->assertJsonFragment(['id' => $webhook1->id]);
        $response->assertJsonFragment(['id' => $webhook2->id]);
    }

    public function test_index_returns_empty_array_when_no_webhooks(): void
    {
        // Arrange
        Passport::actingAs($this->user);

        // Act
        $response = $this->getJson(route('api.v1.webhooks.index'));

        // Assert
        $response->assertOk();
        $response->assertJsonCount(0, 'data');
    }

    public function test_show_returns_webhook_details(): void
    {
        // Arrange
        Passport::actingAs($this->user);
        $webhook = Webhook::factory()->forUser($this->user)->create([
            'url' => 'https://example.com/webhook',
            'events' => ['time_entry.created'],
        ]);

        // Act
        $response = $this->getJson(route('api.v1.webhooks.show', $webhook));

        // Assert
        $response->assertOk();
        $response->assertJson([
            'data' => [
                'id' => $webhook->id,
                'url' => 'https://example.com/webhook',
                'events' => ['time_entry.created'],
                'is_active' => true,
            ],
        ]);
    }

    public function test_show_returns_404_for_other_users_webhook(): void
    {
        // Arrange
        $otherUser = User::factory()->create();
        $webhook = Webhook::factory()->forUser($otherUser)->create();
        Passport::actingAs($this->user);

        // Act
        $response = $this->getJson(route('api.v1.webhooks.show', $webhook));

        // Assert
        $response->assertNotFound();
    }

    public function test_store_creates_webhook(): void
    {
        // Arrange
        Passport::actingAs($this->user);

        // Act
        $response = $this->postJson(route('api.v1.webhooks.store'), [
            'url' => 'https://example.com/webhook',
            'events' => ['time_entry.created', 'invoice.sent'],
            'secret' => 'my-secret-key-123456',
        ]);

        // Assert
        $response->assertCreated();
        $this->assertDatabaseHas('webhooks', [
            'user_id' => $this->user->id,
            'url' => 'https://example.com/webhook',
            'is_active' => true,
        ]);
    }

    public function test_store_generates_secret_if_not_provided(): void
    {
        // Arrange
        Passport::actingAs($this->user);

        // Act
        $response = $this->postJson(route('api.v1.webhooks.store'), [
            'url' => 'https://example.com/webhook',
            'events' => ['time_entry.created'],
        ]);

        // Assert
        $response->assertCreated();
        $webhook = Webhook::where('user_id', $this->user->id)->first();
        $this->assertNotEmpty($webhook->secret);
        $this->assertGreaterThanOrEqual(16, strlen($webhook->secret));
    }

    public function test_store_returns_secret_in_response(): void
    {
        // Arrange
        Passport::actingAs($this->user);

        // Act
        $response = $this->postJson(route('api.v1.webhooks.store'), [
            'url' => 'https://example.com/webhook',
            'events' => ['time_entry.created'],
            'secret' => 'test-secret-12345678',
        ]);

        // Assert
        $response->assertCreated();
        $response->assertJsonFragment(['secret' => 'test-secret-12345678']);
        $response->assertJsonFragment(['message' => 'Webhook created successfully. Save the secret - it will not be shown again.']);
    }

    public function test_store_validates_https_url(): void
    {
        // Arrange
        Passport::actingAs($this->user);

        // Act
        $response = $this->postJson(route('api.v1.webhooks.store'), [
            'url' => 'http://example.com/webhook', // HTTP not allowed
            'events' => ['time_entry.created'],
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('url');
    }

    public function test_store_validates_events_are_valid(): void
    {
        // Arrange
        Passport::actingAs($this->user);

        // Act
        $response = $this->postJson(route('api.v1.webhooks.store'), [
            'url' => 'https://example.com/webhook',
            'events' => ['invalid.event'],
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('events.0');
    }

    public function test_store_requires_at_least_one_event(): void
    {
        // Arrange
        Passport::actingAs($this->user);

        // Act
        $response = $this->postJson(route('api.v1.webhooks.store'), [
            'url' => 'https://example.com/webhook',
            'events' => [],
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('events');
    }

    public function test_update_modifies_webhook(): void
    {
        // Arrange
        Passport::actingAs($this->user);
        $webhook = Webhook::factory()->forUser($this->user)->create([
            'url' => 'https://old-url.com/webhook',
            'events' => ['time_entry.created'],
        ]);

        // Act
        $response = $this->putJson(route('api.v1.webhooks.update', $webhook), [
            'url' => 'https://new-url.com/webhook',
            'events' => ['time_entry.created', 'invoice.sent'],
        ]);

        // Assert
        $response->assertOk();
        $this->assertDatabaseHas('webhooks', [
            'id' => $webhook->id,
            'url' => 'https://new-url.com/webhook',
        ]);
    }

    public function test_update_can_disable_webhook(): void
    {
        // Arrange
        Passport::actingAs($this->user);
        $webhook = Webhook::factory()->forUser($this->user)->create(['is_active' => true]);

        // Act
        $response = $this->putJson(route('api.v1.webhooks.update', $webhook), [
            'is_active' => false,
        ]);

        // Assert
        $response->assertOk();
        $this->assertFalse($webhook->fresh()->is_active);
    }

    public function test_update_cannot_modify_other_users_webhook(): void
    {
        // Arrange
        $otherUser = User::factory()->create();
        $webhook = Webhook::factory()->forUser($otherUser)->create();
        Passport::actingAs($this->user);

        // Act
        $response = $this->putJson(route('api.v1.webhooks.update', $webhook), [
            'url' => 'https://malicious.com/webhook',
        ]);

        // Assert
        $response->assertNotFound();
    }

    public function test_destroy_deletes_webhook(): void
    {
        // Arrange
        Passport::actingAs($this->user);
        $webhook = Webhook::factory()->forUser($this->user)->create();

        // Act
        $response = $this->deleteJson(route('api.v1.webhooks.destroy', $webhook));

        // Assert
        $response->assertOk();
        $this->assertDatabaseMissing('webhooks', ['id' => $webhook->id]);
    }

    public function test_destroy_cannot_delete_other_users_webhook(): void
    {
        // Arrange
        $otherUser = User::factory()->create();
        $webhook = Webhook::factory()->forUser($otherUser)->create();
        Passport::actingAs($this->user);

        // Act
        $response = $this->deleteJson(route('api.v1.webhooks.destroy', $webhook));

        // Assert
        $response->assertNotFound();
        $this->assertDatabaseHas('webhooks', ['id' => $webhook->id]);
    }

    public function test_deliveries_returns_delivery_history(): void
    {
        // Arrange
        Passport::actingAs($this->user);
        $webhook = Webhook::factory()->forUser($this->user)->create();
        $delivery1 = WebhookDelivery::factory()->forWebhook($webhook)->successful()->create();
        $delivery2 = WebhookDelivery::factory()->forWebhook($webhook)->failed()->create();

        // Act
        $response = $this->getJson(route('api.v1.webhooks.deliveries', $webhook));

        // Assert
        $response->assertOk();
        $response->assertJsonCount(2, 'data');
        $response->assertJsonFragment(['id' => $delivery1->id]);
        $response->assertJsonFragment(['id' => $delivery2->id]);
    }

    public function test_deliveries_paginates_results(): void
    {
        // Arrange
        Passport::actingAs($this->user);
        $webhook = Webhook::factory()->forUser($this->user)->create();
        WebhookDelivery::factory()->count(60)->forWebhook($webhook)->create();

        // Act
        $response = $this->getJson(route('api.v1.webhooks.deliveries', $webhook).'?per_page=20');

        // Assert
        $response->assertOk();
        $response->assertJsonCount(20, 'data');
        $response->assertJsonPath('meta.total', 60);
        $response->assertJsonPath('meta.per_page', 20);
    }

    public function test_deliveries_includes_success_status(): void
    {
        // Arrange
        Passport::actingAs($this->user);
        $webhook = Webhook::factory()->forUser($this->user)->create();
        WebhookDelivery::factory()->forWebhook($webhook)->successful()->create();

        // Act
        $response = $this->getJson(route('api.v1.webhooks.deliveries', $webhook));

        // Assert
        $response->assertOk();
        $response->assertJsonPath('data.0.success', true);
    }

    public function test_test_sends_test_webhook(): void
    {
        // Arrange
        Passport::actingAs($this->user);
        $webhook = Webhook::factory()->forUser($this->user)->create();
        Http::fake(['*' => Http::response(['received' => true], 200)]);

        // Act
        $response = $this->postJson(route('api.v1.webhooks.test', $webhook));

        // Assert
        $response->assertOk();
        $response->assertJsonPath('data.success', true);
        $response->assertJsonPath('data.status', 200);
        Http::assertSent(function ($request) {
            return $request->hasHeader('X-Timeclocker-Event')
                && $request->header('X-Timeclocker-Event')[0] === 'webhook.test';
        });
    }

    public function test_test_returns_failure_result_on_error(): void
    {
        // Arrange
        Passport::actingAs($this->user);
        $webhook = Webhook::factory()->forUser($this->user)->create();
        Http::fake(['*' => Http::response([], 500)]);

        // Act
        $response = $this->postJson(route('api.v1.webhooks.test', $webhook));

        // Assert
        $response->assertStatus(400);
        $response->assertJsonPath('data.success', false);
    }

    public function test_available_events_returns_all_event_types(): void
    {
        // Arrange
        Passport::actingAs($this->user);

        // Act
        $response = $this->getJson(route('api.v1.webhooks.available-events'));

        // Assert
        $response->assertOk();
        $response->assertJsonCount(count(Webhook::AVAILABLE_EVENTS), 'data');
        $response->assertJsonFragment(['event' => 'time_entry.created']);
        $response->assertJsonFragment(['event' => 'invoice.sent']);
        $response->assertJsonFragment(['event' => 'payment.received']);
    }

    public function test_available_events_includes_descriptions(): void
    {
        // Arrange
        Passport::actingAs($this->user);

        // Act
        $response = $this->getJson(route('api.v1.webhooks.available-events'));

        // Assert
        $response->assertOk();
        $events = $response->json('data');
        $timeEntryEvent = collect($events)->firstWhere('event', 'time_entry.created');
        $this->assertNotEmpty($timeEntryEvent['description']);
        $this->assertArrayHasKey('resource', $timeEntryEvent);
        $this->assertArrayHasKey('action', $timeEntryEvent);
    }
}
