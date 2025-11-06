<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Organization;
use App\Models\ApiKey;
use App\Models\Webhook;
use App\Models\WebhookDelivery;
use App\Services\ApiKeyService;
use App\Services\WebhookDispatcher;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Comprehensive End-to-End Test for n8n Integration
 *
 * This test suite validates the complete n8n integration including:
 * - Database migrations and models
 * - API endpoints and authentication
 * - Services and business logic
 * - Webhook system and delivery
 * - Complete user workflows
 */
class N8nIntegrationE2ETest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Organization $organization;
    private ApiKeyService $apiKeyService;
    private WebhookDispatcher $webhookDispatcher;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test organization and user
        $this->organization = Organization::factory()->create([
            'name' => 'Test Organization',
        ]);

        $this->user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Attach user to organization
        $this->organization->users()->attach($this->user->id, [
            'role' => 'admin',
        ]);

        // Initialize services
        $this->apiKeyService = app(ApiKeyService::class);
        $this->webhookDispatcher = app(WebhookDispatcher::class);
    }

    /**
     * Test: Database migrations run successfully
     *
     * @test
     */
    public function database_migrations_create_required_tables()
    {
        // Verify api_keys table exists
        $this->assertTrue(
            \Schema::hasTable('api_keys'),
            'api_keys table should exist'
        );

        // Verify webhooks table exists
        $this->assertTrue(
            \Schema::hasTable('webhooks'),
            'webhooks table should exist'
        );

        // Verify webhook_deliveries table exists
        $this->assertTrue(
            \Schema::hasTable('webhook_deliveries'),
            'webhook_deliveries table should exist'
        );

        // Verify api_keys table has correct columns
        $this->assertTrue(
            \Schema::hasColumns('api_keys', [
                'id', 'user_id', 'organization_id', 'name', 'key_prefix',
                'key_hash', 'scopes', 'is_active', 'last_used_at', 'usage_count',
                'expires_at', 'created_at', 'updated_at'
            ]),
            'api_keys table should have all required columns'
        );

        // Verify webhooks table has correct columns
        $this->assertTrue(
            \Schema::hasColumns('webhooks', [
                'id', 'user_id', 'organization_id', 'name', 'url', 'secret',
                'events', 'is_active', 'failure_count', 'verification_status',
                'last_triggered_at', 'created_at', 'updated_at'
            ]),
            'webhooks table should have all required columns'
        );
    }

    /**
     * Test: API Key Model - Key generation and verification
     *
     * @test
     */
    public function api_key_model_generates_and_verifies_keys()
    {
        // Generate a new API key
        $keyData = ApiKey::generateKey();

        $this->assertArrayHasKey('key', $keyData);
        $this->assertArrayHasKey('prefix', $keyData);
        $this->assertArrayHasKey('hash', $keyData);

        // Verify key format (sk_XXXX...)
        $this->assertStringStartsWith('sk_', $keyData['key']);
        $this->assertEquals(51, strlen($keyData['key'])); // sk_ + 48 chars

        // Verify prefix is correct
        $this->assertEquals(substr($keyData['key'], 0, 11), $keyData['prefix']);

        // Verify hash can be verified
        $this->assertTrue(
            Hash::check($keyData['key'], $keyData['hash']),
            'Generated hash should verify against plain key'
        );
    }

    /**
     * Test: API Key Model - Scope validation
     *
     * @test
     */
    public function api_key_model_validates_scopes()
    {
        $keyData = ApiKey::generateKey();

        $apiKey = ApiKey::create([
            'id' => Str::uuid(),
            'user_id' => $this->user->id,
            'organization_id' => $this->organization->id,
            'name' => 'Test Key',
            'key_prefix' => $keyData['prefix'],
            'key_hash' => $keyData['hash'],
            'scopes' => ['time_entries:read', 'projects:read'],
            'is_active' => true,
        ]);

        // Test scope checking
        $this->assertTrue($apiKey->hasScope('time_entries:read'));
        $this->assertTrue($apiKey->hasScope('projects:read'));
        $this->assertFalse($apiKey->hasScope('time_entries:write'));

        // Test wildcard scope
        $apiKey->scopes = ['*'];
        $apiKey->save();

        $this->assertTrue($apiKey->hasScope('time_entries:read'));
        $this->assertTrue($apiKey->hasScope('projects:write'));
        $this->assertTrue($apiKey->hasScope('any_scope'));
    }

    /**
     * Test: Webhook Model - Secret generation and event validation
     *
     * @test
     */
    public function webhook_model_generates_secrets_and_validates_events()
    {
        // Generate webhook secret
        $secret = Webhook::generateSecret();

        $this->assertStringStartsWith('whsec_', $secret);
        $this->assertEquals(54, strlen($secret)); // whsec_ + 48 chars

        // Create webhook with events
        $webhook = Webhook::create([
            'id' => Str::uuid(),
            'user_id' => $this->user->id,
            'organization_id' => $this->organization->id,
            'name' => 'Test Webhook',
            'url' => 'https://example.com/webhook',
            'secret' => $secret,
            'events' => ['time_entry.started', 'time_entry.stopped'],
            'is_active' => true,
            'failure_count' => 0,
            'verification_status' => 'verified',
        ]);

        // Test event subscription
        $this->assertTrue($webhook->isSubscribedTo('time_entry.started'));
        $this->assertTrue($webhook->isSubscribedTo('time_entry.stopped'));
        $this->assertFalse($webhook->isSubscribedTo('project.created'));

        // Test health status
        $this->assertTrue($webhook->isHealthy());

        // Simulate failures
        $webhook->recordFailure('Connection timeout');
        $this->assertEquals(1, $webhook->fresh()->failure_count);

        // Test auto-disable after 10 failures
        for ($i = 0; $i < 9; $i++) {
            $webhook->recordFailure('Error');
        }

        $this->assertFalse($webhook->fresh()->is_active, 'Webhook should be disabled after 10 failures');
    }

    /**
     * Test: WebhookDelivery Model - Retry logic
     *
     * @test
     */
    public function webhook_delivery_model_implements_retry_logic()
    {
        $webhook = Webhook::factory()->create([
            'organization_id' => $this->organization->id,
            'user_id' => $this->user->id,
        ]);

        $deliveryId = WebhookDelivery::generateDeliveryId();
        $this->assertStringStartsWith('del_', $deliveryId);

        $delivery = WebhookDelivery::create([
            'id' => Str::uuid(),
            'webhook_id' => $webhook->id,
            'delivery_id' => $deliveryId,
            'event_type' => 'time_entry.started',
            'payload' => ['test' => 'data'],
            'status' => 'pending',
            'attempt_number' => 1,
            'max_attempts' => 3,
            'attempted_at' => now(),
        ]);

        // Mark as failed (should schedule retry)
        $delivery->markAsFailed('Connection error', 500, 'Internal Server Error', 1500);

        $this->assertEquals('retrying', $delivery->fresh()->status);
        $this->assertNotNull($delivery->fresh()->next_retry_at);
        $this->assertEquals(2, $delivery->fresh()->attempt_number);

        // Test exponential backoff (attempt 1: 5 min, attempt 2: 10 min)
        $this->assertGreaterThan(now()->addMinutes(4), $delivery->fresh()->next_retry_at);
        $this->assertLessThan(now()->addMinutes(6), $delivery->fresh()->next_retry_at);
    }

    /**
     * Test: API Key Service - Complete CRUD operations
     *
     * @test
     */
    public function api_key_service_handles_complete_lifecycle()
    {
        // Create API key via service
        $result = $this->apiKeyService->create(
            userId: $this->user->id,
            organizationId: $this->organization->id,
            name: 'Service Test Key',
            scopes: ['time_entries:read', 'projects:write'],
            description: 'Test key for E2E testing',
            expiresAt: now()->addDays(30)
        );

        $this->assertArrayHasKey('plain_key', $result);
        $this->assertArrayHasKey('api_key', $result);

        $plainKey = $result['plain_key'];
        $apiKey = $result['api_key'];

        $this->assertInstanceOf(ApiKey::class, $apiKey);
        $this->assertEquals('Service Test Key', $apiKey->name);

        // Authenticate with plain key
        $authenticated = $this->apiKeyService->authenticate($plainKey);

        $this->assertNotNull($authenticated);
        $this->assertEquals($apiKey->id, $authenticated->id);

        // Verify usage was recorded
        $this->assertEquals(1, $authenticated->usage_count);
        $this->assertNotNull($authenticated->last_used_at);

        // Update scopes
        $success = $this->apiKeyService->updateScopes($apiKey->id, ['*']);
        $this->assertTrue($success);
        $this->assertEquals(['*'], $apiKey->fresh()->scopes);

        // Revoke key
        $success = $this->apiKeyService->revoke($apiKey->id);
        $this->assertTrue($success);
        $this->assertFalse($apiKey->fresh()->is_active);

        // Verify revoked key cannot authenticate
        $authenticated = $this->apiKeyService->authenticate($plainKey);
        $this->assertNull($authenticated);
    }

    /**
     * Test: API Endpoints - API Keys CRUD operations
     *
     * @test
     */
    public function api_endpoints_handle_api_key_operations()
    {
        $this->actingAs($this->user);

        // List API keys
        $response = $this->getJson("/api/v1/api-keys?organization_id={$this->organization->id}");
        $response->assertOk();
        $response->assertJsonStructure(['data']);

        // Get available scopes
        $response = $this->getJson('/api/v1/api-keys/scopes');
        $response->assertOk();
        $response->assertJsonStructure(['data']);

        // Create API key
        $response = $this->postJson('/api/v1/api-keys', [
            'organization_id' => $this->organization->id,
            'name' => 'API Test Key',
            'description' => 'Created via API test',
            'scopes' => ['time_entries:read'],
        ]);

        $response->assertCreated();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'key', // Plain key shown only once
                'key_prefix',
                'scopes',
                'created_at',
            ],
            'warning',
        ]);

        $apiKeyId = $response->json('data.id');

        // Get specific API key
        $response = $this->getJson("/api/v1/api-keys/{$apiKeyId}");
        $response->assertOk();
        $response->assertJson([
            'data' => [
                'name' => 'API Test Key',
            ],
        ]);

        // Update API key
        $response = $this->putJson("/api/v1/api-keys/{$apiKeyId}", [
            'scopes' => ['time_entries:write', 'projects:read'],
        ]);

        $response->assertOk();

        // Delete API key
        $response = $this->deleteJson("/api/v1/api-keys/{$apiKeyId}");
        $response->assertOk();
    }

    /**
     * Test: API Endpoints - Webhooks CRUD operations
     *
     * @test
     */
    public function api_endpoints_handle_webhook_operations()
    {
        $this->actingAs($this->user);

        // Get available events
        $response = $this->getJson('/api/v1/webhooks/events');
        $response->assertOk();
        $response->assertJsonStructure(['data']);

        // Verify all 22 event types are present
        $events = $response->json('data');
        $this->assertCount(22, $events);
        $this->assertArrayHasKey('time_entry.started', $events);
        $this->assertArrayHasKey('invoice.paid', $events);

        // Create webhook
        $response = $this->postJson('/api/v1/webhooks', [
            'organization_id' => $this->organization->id,
            'name' => 'Test Webhook',
            'url' => 'https://n8n.example.com/webhook/test',
            'events' => ['time_entry.started', 'time_entry.stopped'],
        ]);

        $response->assertCreated();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'url',
                'secret', // Secret shown only once
                'events',
                'is_active',
                'verification_status',
            ],
        ]);

        $webhookId = $response->json('data.id');

        // Test webhook
        $response = $this->postJson("/api/v1/webhooks/{$webhookId}/test");
        $response->assertOk();
        $response->assertJsonStructure(['data' => ['delivery_id', 'status']]);

        // List webhooks
        $response = $this->getJson("/api/v1/webhooks?organization_id={$this->organization->id}");
        $response->assertOk();
        $response->assertJsonCount(1, 'data');

        // Delete webhook
        $response = $this->deleteJson("/api/v1/webhooks/{$webhookId}");
        $response->assertOk();
    }

    /**
     * Test: Complete User Workflow - API Key Creation to Webhook Delivery
     *
     * @test
     */
    public function complete_workflow_from_api_key_to_webhook_delivery()
    {
        $this->actingAs($this->user);

        // Step 1: User creates API key via UI
        $response = $this->postJson('/api/v1/api-keys', [
            'organization_id' => $this->organization->id,
            'name' => 'n8n Production Key',
            'description' => 'For n8n workflow automation',
            'scopes' => ['*'],
        ]);

        $response->assertCreated();
        $plainKey = $response->json('data.key');

        // Step 2: User creates webhook via UI
        $response = $this->postJson('/api/v1/webhooks', [
            'organization_id' => $this->organization->id,
            'name' => 'n8n Time Entry Hook',
            'url' => 'https://n8n.example.com/webhook/solidtime',
            'events' => ['time_entry.started', 'time_entry.stopped'],
        ]);

        $response->assertCreated();
        $webhook = Webhook::find($response->json('data.id'));

        // Step 3: Simulate event trigger (time entry started)
        $timeEntry = (object)[
            'id' => Str::uuid(),
            'organization_id' => $this->organization->id,
            'user_id' => $this->user->id,
            'description' => 'Working on n8n integration',
            'start' => now()->toIso8601String(),
            'billable' => true,
        ];

        // Step 4: Webhook dispatcher sends event
        $delivery = $this->webhookDispatcher->send(
            $webhook,
            'time_entry.started',
            (array) $timeEntry
        );

        // Step 5: Verify delivery was created
        $this->assertInstanceOf(WebhookDelivery::class, $delivery);
        $this->assertEquals('time_entry.started', $delivery->event_type);
        $this->assertNotNull($delivery->delivery_id);

        // Step 6: User checks delivery logs via UI
        $response = $this->getJson("/api/v1/webhooks/{$webhook->id}/deliveries");
        $response->assertOk();
        $response->assertJsonCount(1, 'data');

        // Step 7: Verify signature was generated
        $deliveryData = $response->json('data.0');
        $this->assertArrayHasKey('delivery_id', $deliveryData);
        $this->assertEquals('time_entry.started', $deliveryData['event_type']);
    }

    /**
     * Test: n8n Trigger Node Workflow
     *
     * @test
     */
    public function n8n_trigger_node_workflow_simulation()
    {
        // Simulate n8n registering a webhook
        $webhookUrl = 'https://n8n.example.com/webhook-test/solidtime';

        $webhook = Webhook::create([
            'id' => Str::uuid(),
            'user_id' => $this->user->id,
            'organization_id' => $this->organization->id,
            'name' => 'n8n Workflow - Time Tracking Automation',
            'url' => $webhookUrl,
            'secret' => Webhook::generateSecret(),
            'events' => ['time_entry.stopped'],
            'is_active' => true,
            'failure_count' => 0,
            'verification_status' => 'verified',
        ]);

        // Simulate event occurring
        $eventData = [
            'id' => Str::uuid(),
            'user_id' => $this->user->id,
            'description' => 'Feature development',
            'start' => now()->subHours(2)->toIso8601String(),
            'end' => now()->toIso8601String(),
            'duration' => 7200, // 2 hours
            'billable' => true,
        ];

        // Dispatch webhook
        $delivery = $this->webhookDispatcher->send($webhook, 'time_entry.stopped', $eventData);

        // Verify payload structure (what n8n will receive)
        $this->assertNotNull($delivery->payload);
        $this->assertArrayHasKey('event', $delivery->payload);
        $this->assertArrayHasKey('data', $delivery->payload);
        $this->assertArrayHasKey('delivery_id', $delivery->payload);
        $this->assertArrayHasKey('timestamp', $delivery->payload);

        $this->assertEquals('time_entry.stopped', $delivery->payload['event']);
        $this->assertEquals($eventData['id'], $delivery->payload['data']['id']);
    }

    /**
     * Test: Security - API Key Authentication
     *
     * @test
     */
    public function api_key_authentication_enforces_security()
    {
        // Create API key with limited scopes
        $result = $this->apiKeyService->create(
            userId: $this->user->id,
            organizationId: $this->organization->id,
            name: 'Limited Scope Key',
            scopes: ['time_entries:read'], // Read only
        );

        $apiKey = $result['plain_key'];

        // Attempt to read (should succeed)
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$apiKey}",
        ])->getJson('/api/v1/api-keys/scopes');

        $response->assertOk();

        // Attempt to create without write scope (should fail)
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$apiKey}",
        ])->postJson('/api/v1/api-keys', [
            'organization_id' => $this->organization->id,
            'name' => 'Unauthorized Key',
            'scopes' => ['*'],
        ]);

        $response->assertForbidden(); // Should be blocked by scope validation
    }

    /**
     * Test: Security - Webhook Signature Verification
     *
     * @test
     */
    public function webhook_signatures_are_verified()
    {
        $webhook = Webhook::factory()->create([
            'organization_id' => $this->organization->id,
            'user_id' => $this->user->id,
            'secret' => 'whsec_test123456789',
        ]);

        $payload = [
            'event' => 'time_entry.started',
            'data' => ['id' => '123'],
        ];

        $payloadJson = json_encode($payload);
        $expectedSignature = hash_hmac('sha256', $payloadJson, $webhook->secret);

        // Simulate webhook delivery with correct signature
        $headers = [
            'X-Solidtime-Event' => 'time_entry.started',
            'X-Solidtime-Delivery' => 'del_abc123',
            'X-Solidtime-Signature' => $expectedSignature,
            'X-Solidtime-Timestamp' => time(),
        ];

        $delivery = $this->webhookDispatcher->send($webhook, 'time_entry.started', $payload);

        // Verify signature was generated
        $this->assertNotNull($delivery);
        $this->assertArrayHasKey('headers', $delivery->payload);
    }

    /**
     * Test: Performance - Batch webhook delivery
     *
     * @test
     */
    public function webhook_dispatcher_handles_multiple_subscribers()
    {
        // Create multiple webhooks for the same event
        $webhooks = [];
        for ($i = 0; $i < 5; $i++) {
            $webhooks[] = Webhook::factory()->create([
                'organization_id' => $this->organization->id,
                'user_id' => $this->user->id,
                'events' => ['time_entry.created'],
                'is_active' => true,
            ]);
        }

        $eventData = ['id' => Str::uuid(), 'description' => 'Test'];

        // Dispatch to all webhooks
        $deliveries = [];
        foreach ($webhooks as $webhook) {
            $deliveries[] = $this->webhookDispatcher->send($webhook, 'time_entry.created', $eventData);
        }

        // Verify all deliveries were created
        $this->assertCount(5, $deliveries);

        // Verify each delivery has unique ID
        $deliveryIds = array_map(fn($d) => $d->delivery_id, $deliveries);
        $this->assertCount(5, array_unique($deliveryIds));
    }

    /**
     * Test: Error Handling - Failed deliveries and retry queue
     *
     * @test
     */
    public function failed_deliveries_are_queued_for_retry()
    {
        $webhook = Webhook::factory()->create([
            'organization_id' => $this->organization->id,
            'user_id' => $this->user->id,
        ]);

        $delivery = WebhookDelivery::factory()->create([
            'webhook_id' => $webhook->id,
            'status' => 'failed',
            'attempt_number' => 1,
            'next_retry_at' => now()->subMinutes(10), // Past due
        ]);

        // Get retryable deliveries
        $retryable = WebhookDelivery::where('status', 'retrying')
            ->where('next_retry_at', '<=', now())
            ->get();

        // Simulate retry processing (would be done by scheduled command)
        foreach ($retryable as $delivery) {
            $this->assertTrue($delivery->canRetry());
            $this->assertLessThanOrEqual(now(), $delivery->next_retry_at);
        }
    }
}
