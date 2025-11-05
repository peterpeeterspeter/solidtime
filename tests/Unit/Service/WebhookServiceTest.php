<?php

declare(strict_types=1);

namespace Tests\Unit\Service;

use App\Models\Webhook;
use App\Models\WebhookDelivery;
use App\Services\WebhookService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

#[CoversClass(WebhookService::class)]
class WebhookServiceTest extends TestCase
{
    use RefreshDatabase;

    private WebhookService $webhookService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->webhookService = new WebhookService();
    }

    public function test_dispatch_sends_to_all_subscribed_webhooks(): void
    {
        // Arrange
        $webhook1 = Webhook::factory()->create(['events' => ['time_entry.created']]);
        $webhook2 = Webhook::factory()->create(['events' => ['time_entry.created', 'invoice.sent']]);
        $webhook3 = Webhook::factory()->create(['events' => ['invoice.sent']]); // Not subscribed

        Http::fake();

        // Act
        $this->webhookService->dispatch('time_entry.created', ['id' => '123']);

        // Assert
        Http::assertSentCount(2); // Only webhook1 and webhook2
    }

    public function test_dispatch_does_not_send_to_inactive_webhooks(): void
    {
        // Arrange
        Webhook::factory()->inactive()->create(['events' => ['time_entry.created']]);

        Http::fake();

        // Act
        $this->webhookService->dispatch('time_entry.created', ['id' => '123']);

        // Assert
        Http::assertNothingSent();
    }

    public function test_send_webhook_creates_delivery_record(): void
    {
        // Arrange
        $webhook = Webhook::factory()->create();
        Http::fake(['*' => Http::response(['received' => true], 200)]);

        // Act
        $this->webhookService->sendWebhook($webhook, 'time_entry.created', ['id' => '123']);

        // Assert
        $this->assertDatabaseHas('webhook_deliveries', [
            'webhook_id' => $webhook->id,
            'event_type' => 'time_entry.created',
            'attempt' => 1,
        ]);
    }

    public function test_send_webhook_includes_signature_header(): void
    {
        // Arrange
        $webhook = Webhook::factory()->create(['secret' => 'test-secret']);
        Http::fake();

        // Act
        $this->webhookService->sendWebhook($webhook, 'time_entry.created', ['id' => '123']);

        // Assert
        Http::assertSent(function ($request) {
            return $request->hasHeader('X-Timeclocker-Signature')
                && str_starts_with($request->header('X-Timeclocker-Signature')[0], 'sha256=');
        });
    }

    public function test_send_webhook_includes_custom_headers(): void
    {
        // Arrange
        $webhook = Webhook::factory()->create();
        Http::fake();

        // Act
        $this->webhookService->sendWebhook($webhook, 'time_entry.created', ['id' => '123']);

        // Assert
        Http::assertSent(function ($request) {
            return $request->hasHeader('X-Timeclocker-Event')
                && $request->hasHeader('X-Timeclocker-Delivery-ID')
                && $request->hasHeader('User-Agent');
        });
    }

    public function test_send_webhook_increments_success_count_on_200(): void
    {
        // Arrange
        $webhook = Webhook::factory()->create(['delivery_success_count' => 5]);
        Http::fake(['*' => Http::response(['received' => true], 200)]);

        // Act
        $this->webhookService->sendWebhook($webhook, 'time_entry.created', ['id' => '123']);

        // Assert
        $this->assertSame(6, $webhook->fresh()->delivery_success_count);
        $this->assertNotNull($webhook->fresh()->last_delivery_at);
    }

    public function test_send_webhook_increments_failure_count_on_error(): void
    {
        // Arrange
        $webhook = Webhook::factory()->create(['delivery_failure_count' => 2]);
        Http::fake(['*' => Http::response([], 500)]);

        // Act
        $this->webhookService->sendWebhook($webhook, 'time_entry.created', ['id' => '123']);

        // Assert
        $this->assertSame(3, $webhook->fresh()->delivery_failure_count);
    }

    public function test_send_webhook_disables_after_50_failures(): void
    {
        // Arrange
        $webhook = Webhook::factory()->create([
            'delivery_failure_count' => 49,
            'is_active' => true,
        ]);
        Http::fake(['*' => Http::response([], 500)]);

        // Act
        $this->webhookService->sendWebhook($webhook, 'time_entry.created', ['id' => '123']);

        // Assert
        $this->assertFalse($webhook->fresh()->is_active);
    }

    public function test_send_webhook_records_response_status_and_body(): void
    {
        // Arrange
        $webhook = Webhook::factory()->create();
        Http::fake(['*' => Http::response(['message' => 'Webhook received'], 200)]);

        // Act
        $this->webhookService->sendWebhook($webhook, 'time_entry.created', ['id' => '123']);

        // Assert
        $delivery = WebhookDelivery::where('webhook_id', $webhook->id)->first();
        $this->assertSame(200, $delivery->response_status);
        $this->assertStringContainsString('Webhook received', $delivery->response_body);
    }

    public function test_generate_signature_creates_valid_hmac(): void
    {
        // Arrange
        $secret = 'test-secret';
        $payload = ['id' => '123', 'event' => 'test'];

        // Act
        $signature = $this->webhookService->generateSignature($secret, $payload);

        // Assert
        $this->assertIsString($signature);
        $this->assertSame(64, strlen($signature)); // SHA256 hex is 64 chars
    }

    public function test_verify_signature_validates_correct_signature(): void
    {
        // Arrange
        $secret = 'test-secret';
        $payload = ['id' => '123'];
        $signature = $this->webhookService->generateSignature($secret, $payload);

        // Act
        $isValid = $this->webhookService->verifySignature($signature, $secret, $payload);

        // Assert
        $this->assertTrue($isValid);
    }

    public function test_verify_signature_rejects_incorrect_signature(): void
    {
        // Arrange
        $secret = 'test-secret';
        $payload = ['id' => '123'];
        $wrongSignature = 'wrong-signature';

        // Act
        $isValid = $this->webhookService->verifySignature($wrongSignature, $secret, $payload);

        // Assert
        $this->assertFalse($isValid);
    }

    public function test_verify_signature_handles_sha256_prefix(): void
    {
        // Arrange
        $secret = 'test-secret';
        $payload = ['id' => '123'];
        $signature = 'sha256='.$this->webhookService->generateSignature($secret, $payload);

        // Act
        $isValid = $this->webhookService->verifySignature($signature, $secret, $payload);

        // Assert
        $this->assertTrue($isValid);
    }

    public function test_send_test_webhook_returns_success_result(): void
    {
        // Arrange
        $webhook = Webhook::factory()->create();
        Http::fake(['*' => Http::response(['received' => true], 200)]);

        // Act
        $result = $this->webhookService->sendTestWebhook($webhook);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertSame(200, $result['status']);
        $this->assertStringContainsString('successfully', $result['message']);
    }

    public function test_send_test_webhook_returns_failure_result_on_error(): void
    {
        // Arrange
        $webhook = Webhook::factory()->create();
        Http::fake(['*' => Http::response([], 500)]);

        // Act
        $result = $this->webhookService->sendTestWebhook($webhook);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertSame(500, $result['status']);
        $this->assertStringContainsString('failed', $result['message']);
    }

    public function test_send_test_webhook_dispatches_webhook_test_event(): void
    {
        // Arrange
        $webhook = Webhook::factory()->create();
        Http::fake();

        // Act
        $this->webhookService->sendTestWebhook($webhook);

        // Assert
        Http::assertSent(function ($request) {
            return $request->hasHeader('X-Timeclocker-Event')
                && $request->header('X-Timeclocker-Event')[0] === 'webhook.test';
        });
    }
}
