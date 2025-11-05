<?php

declare(strict_types=1);

namespace Tests\Unit\Endpoint\Webhooks;

use App\Http\Controllers\Webhooks\PayPalWebhookController;
use App\Services\Payment\PayPalPaymentGateway;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCaseWithDatabase;
use PHPUnit\Framework\Attributes\UsesClass;

#[UsesClass(PayPalWebhookController::class)]
class PayPalWebhookControllerTest extends TestCaseWithDatabase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_handle_processes_valid_paypal_webhook(): void
    {
        // Arrange
        $webhookId = 'WH-test-webhook-id';
        Config::set('services.paypal.webhook_id', $webhookId);

        $payload = json_encode([
            'id' => 'WH-evt-test123',
            'event_type' => 'PAYMENT.CAPTURE.COMPLETED',
            'resource' => [
                'id' => 'CAPTURE-123',
                'amount' => [
                    'value' => '100.00',
                    'currency_code' => 'USD',
                ],
            ],
        ]);

        $signature = 'valid_paypal_signature';

        $mockGateway = Mockery::mock(PayPalPaymentGateway::class);
        $mockGateway->shouldReceive('verifyWebhookSignature')
            ->once()
            ->with($payload, $signature, $webhookId)
            ->andReturn(true);

        $mockGateway->shouldReceive('handleWebhook')
            ->once()
            ->with(Mockery::on(function ($event) {
                return $event['event_type'] === 'PAYMENT.CAPTURE.COMPLETED';
            }));

        $this->app->instance(PayPalPaymentGateway::class, $mockGateway);

        Log::shouldReceive('info')
            ->once()
            ->with('PayPal webhook received', [
                'type' => 'PAYMENT.CAPTURE.COMPLETED',
                'id' => 'WH-evt-test123',
            ]);

        // Act
        $response = $this->postJson('/api/webhooks/paypal', [], [
            'PAYPAL-TRANSMISSION-SIG' => $signature,
        ], [], $payload);

        // Assert
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    public function test_handle_fails_with_missing_signature(): void
    {
        // Arrange
        Config::set('services.paypal.webhook_id', 'WH-test-webhook-id');

        $payload = json_encode([
            'id' => 'WH-evt-test123',
            'event_type' => 'PAYMENT.CAPTURE.COMPLETED',
        ]);

        Log::shouldReceive('warning')
            ->once()
            ->with('PayPal webhook missing signature or webhook ID');

        // Act
        $response = $this->postJson('/api/webhooks/paypal', [], [], [], $payload);

        // Assert
        $response->assertStatus(400);
        $response->assertJson(['error' => 'Missing signature or webhook ID']);
    }

    public function test_handle_fails_with_missing_webhook_id(): void
    {
        // Arrange
        Config::set('services.paypal.webhook_id', null);

        $payload = json_encode([
            'id' => 'WH-evt-test123',
            'event_type' => 'PAYMENT.CAPTURE.COMPLETED',
        ]);

        Log::shouldReceive('warning')
            ->once()
            ->with('PayPal webhook missing signature or webhook ID');

        // Act
        $response = $this->postJson('/api/webhooks/paypal', [], [
            'PAYPAL-TRANSMISSION-SIG' => 'some_signature',
        ], [], $payload);

        // Assert
        $response->assertStatus(400);
        $response->assertJson(['error' => 'Missing signature or webhook ID']);
    }

    public function test_handle_fails_with_invalid_signature(): void
    {
        // Arrange
        $webhookId = 'WH-test-webhook-id';
        Config::set('services.paypal.webhook_id', $webhookId);

        $payload = json_encode([
            'id' => 'WH-evt-test123',
            'event_type' => 'PAYMENT.CAPTURE.COMPLETED',
        ]);

        $invalidSignature = 'invalid_signature';

        $mockGateway = Mockery::mock(PayPalPaymentGateway::class);
        $mockGateway->shouldReceive('verifyWebhookSignature')
            ->once()
            ->with($payload, $invalidSignature, $webhookId)
            ->andReturn(false);

        $this->app->instance(PayPalPaymentGateway::class, $mockGateway);

        Log::shouldReceive('warning')
            ->once()
            ->with('PayPal webhook signature verification failed');

        // Act
        $response = $this->postJson('/api/webhooks/paypal', [], [
            'PAYPAL-TRANSMISSION-SIG' => $invalidSignature,
        ], [], $payload);

        // Assert
        $response->assertStatus(401);
        $response->assertJson(['error' => 'Invalid signature']);
    }

    public function test_handle_fails_with_invalid_payload_format(): void
    {
        // Arrange
        $webhookId = 'WH-test-webhook-id';
        Config::set('services.paypal.webhook_id', $webhookId);

        $payload = 'invalid json';
        $signature = 'valid_signature';

        $mockGateway = Mockery::mock(PayPalPaymentGateway::class);
        $mockGateway->shouldReceive('verifyWebhookSignature')
            ->once()
            ->andReturn(true);

        $this->app->instance(PayPalPaymentGateway::class, $mockGateway);

        Log::shouldReceive('warning')
            ->once()
            ->with('Invalid PayPal webhook payload');

        // Act
        $response = $this->postJson('/api/webhooks/paypal', [], [
            'PAYPAL-TRANSMISSION-SIG' => $signature,
        ], [], $payload);

        // Assert
        $response->assertStatus(400);
        $response->assertJson(['error' => 'Invalid payload']);
    }

    public function test_handle_fails_with_payload_missing_event_type(): void
    {
        // Arrange
        $webhookId = 'WH-test-webhook-id';
        Config::set('services.paypal.webhook_id', $webhookId);

        $payload = json_encode([
            'id' => 'WH-evt-test123',
            // Missing 'event_type' field
            'resource' => [],
        ]);

        $signature = 'valid_signature';

        $mockGateway = Mockery::mock(PayPalPaymentGateway::class);
        $mockGateway->shouldReceive('verifyWebhookSignature')
            ->once()
            ->andReturn(true);

        $this->app->instance(PayPalPaymentGateway::class, $mockGateway);

        Log::shouldReceive('warning')
            ->once()
            ->with('Invalid PayPal webhook payload');

        // Act
        $response = $this->postJson('/api/webhooks/paypal', [], [
            'PAYPAL-TRANSMISSION-SIG' => $signature,
        ], [], $payload);

        // Assert
        $response->assertStatus(400);
        $response->assertJson(['error' => 'Invalid payload']);
    }

    public function test_handle_logs_webhook_event_details(): void
    {
        // Arrange
        $webhookId = 'WH-test-webhook-id';
        Config::set('services.paypal.webhook_id', $webhookId);

        $payload = json_encode([
            'id' => 'WH-evt-refund123',
            'event_type' => 'PAYMENT.CAPTURE.REFUNDED',
            'resource' => [
                'id' => 'REFUND-123',
            ],
        ]);

        $signature = 'valid_signature';

        $mockGateway = Mockery::mock(PayPalPaymentGateway::class);
        $mockGateway->shouldReceive('verifyWebhookSignature')
            ->once()
            ->andReturn(true);

        $mockGateway->shouldReceive('handleWebhook')
            ->once();

        $this->app->instance(PayPalPaymentGateway::class, $mockGateway);

        Log::shouldReceive('info')
            ->once()
            ->with('PayPal webhook received', [
                'type' => 'PAYMENT.CAPTURE.REFUNDED',
                'id' => 'WH-evt-refund123',
            ]);

        // Act
        $response = $this->postJson('/api/webhooks/paypal', [], [
            'PAYPAL-TRANSMISSION-SIG' => $signature,
        ], [], $payload);

        // Assert
        $response->assertStatus(200);
    }

    public function test_handle_catches_gateway_exceptions(): void
    {
        // Arrange
        $webhookId = 'WH-test-webhook-id';
        Config::set('services.paypal.webhook_id', $webhookId);

        $payload = json_encode([
            'id' => 'WH-evt-test123',
            'event_type' => 'PAYMENT.CAPTURE.COMPLETED',
        ]);

        $signature = 'valid_signature';

        $mockGateway = Mockery::mock(PayPalPaymentGateway::class);
        $mockGateway->shouldReceive('verifyWebhookSignature')
            ->once()
            ->andReturn(true);

        $mockGateway->shouldReceive('handleWebhook')
            ->once()
            ->andThrow(new \Exception('PayPal processing failed'));

        $this->app->instance(PayPalPaymentGateway::class, $mockGateway);

        Log::shouldReceive('info')->once();
        Log::shouldReceive('error')
            ->once()
            ->with('PayPal webhook processing failed', Mockery::on(function ($context) {
                return isset($context['error']) && $context['error'] === 'PayPal processing failed';
            }));

        // Act
        $response = $this->postJson('/api/webhooks/paypal', [], [
            'PAYPAL-TRANSMISSION-SIG' => $signature,
        ], [], $payload);

        // Assert
        $response->assertStatus(500);
        $response->assertJson([
            'error' => 'Webhook processing failed',
            'message' => 'PayPal processing failed',
        ]);
    }

    public function test_handle_processes_payment_capture_denied_event(): void
    {
        // Arrange
        $webhookId = 'WH-test-webhook-id';
        Config::set('services.paypal.webhook_id', $webhookId);

        $payload = json_encode([
            'id' => 'WH-evt-denied123',
            'event_type' => 'PAYMENT.CAPTURE.DENIED',
            'resource' => [
                'id' => 'CAPTURE-123',
                'status' => 'DECLINED',
            ],
        ]);

        $signature = 'valid_signature';

        $mockGateway = Mockery::mock(PayPalPaymentGateway::class);
        $mockGateway->shouldReceive('verifyWebhookSignature')
            ->once()
            ->andReturn(true);

        $mockGateway->shouldReceive('handleWebhook')
            ->once()
            ->with(Mockery::on(function ($event) {
                return $event['event_type'] === 'PAYMENT.CAPTURE.DENIED';
            }));

        $this->app->instance(PayPalPaymentGateway::class, $mockGateway);

        Log::shouldReceive('info')->once();

        // Act
        $response = $this->postJson('/api/webhooks/paypal', [], [
            'PAYPAL-TRANSMISSION-SIG' => $signature,
        ], [], $payload);

        // Assert
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    public function test_handle_verifies_signature_using_paypal_gateway(): void
    {
        // Arrange
        $webhookId = 'WH-test-webhook-id';
        Config::set('services.paypal.webhook_id', $webhookId);

        $payload = json_encode([
            'id' => 'WH-evt-test123',
            'event_type' => 'PAYMENT.CAPTURE.COMPLETED',
        ]);

        $signature = 'test_signature_to_verify';

        $mockGateway = Mockery::mock(PayPalPaymentGateway::class);

        // Verify that the signature verification is called with exact parameters
        $mockGateway->shouldReceive('verifyWebhookSignature')
            ->once()
            ->with($payload, $signature, $webhookId)
            ->andReturn(true);

        $mockGateway->shouldReceive('handleWebhook')->once();

        $this->app->instance(PayPalPaymentGateway::class, $mockGateway);

        Log::shouldReceive('info')->once();

        // Act
        $response = $this->postJson('/api/webhooks/paypal', [], [
            'PAYPAL-TRANSMISSION-SIG' => $signature,
        ], [], $payload);

        // Assert
        $response->assertStatus(200);
    }
}
