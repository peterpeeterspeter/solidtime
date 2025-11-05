<?php

declare(strict_types=1);

namespace Tests\Unit\Endpoint\Webhooks;

use App\Http\Controllers\Webhooks\StripeWebhookController;
use App\Services\Payment\StripePaymentGateway;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCaseWithDatabase;
use PHPUnit\Framework\Attributes\UsesClass;

#[UsesClass(StripeWebhookController::class)]
class StripeWebhookControllerTest extends TestCaseWithDatabase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function generateValidStripeSignature(string $payload, string $secret, ?int $timestamp = null): string
    {
        $timestamp = $timestamp ?? time();
        $signedPayload = "{$timestamp}.{$payload}";
        $signature = hash_hmac('sha256', $signedPayload, $secret);

        return "t={$timestamp},v1={$signature}";
    }

    public function test_handle_processes_valid_stripe_webhook(): void
    {
        // Arrange
        $webhookSecret = 'whsec_test_secret';
        Config::set('services.stripe.webhook_secret', $webhookSecret);

        $payload = json_encode([
            'id' => 'evt_test123',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_test123',
                    'amount' => 1000,
                ],
            ],
        ]);

        $signature = $this->generateValidStripeSignature($payload, $webhookSecret);

        $mockGateway = Mockery::mock(StripePaymentGateway::class);
        $mockGateway->shouldReceive('handleWebhook')
            ->once()
            ->with(Mockery::on(function ($event) {
                return $event['type'] === 'payment_intent.succeeded';
            }));

        $this->app->instance(StripePaymentGateway::class, $mockGateway);

        // Act
        $response = $this->postJson('/api/webhooks/stripe', [], [
            'Stripe-Signature' => $signature,
        ], [], $payload);

        // Assert
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    public function test_handle_fails_with_missing_signature(): void
    {
        // Arrange
        Config::set('services.stripe.webhook_secret', 'whsec_test_secret');

        $payload = json_encode([
            'id' => 'evt_test123',
            'type' => 'payment_intent.succeeded',
        ]);

        Log::shouldReceive('warning')
            ->once()
            ->with('Stripe webhook missing signature or secret');

        // Act
        $response = $this->postJson('/api/webhooks/stripe', [], [], [], $payload);

        // Assert
        $response->assertStatus(400);
        $response->assertJson(['error' => 'Missing signature or secret']);
    }

    public function test_handle_fails_with_missing_webhook_secret(): void
    {
        // Arrange
        Config::set('services.stripe.webhook_secret', null);

        $payload = json_encode([
            'id' => 'evt_test123',
            'type' => 'payment_intent.succeeded',
        ]);

        Log::shouldReceive('warning')
            ->once()
            ->with('Stripe webhook missing signature or secret');

        // Act
        $response = $this->postJson('/api/webhooks/stripe', [], [
            'Stripe-Signature' => 't=123,v1=abc',
        ], [], $payload);

        // Assert
        $response->assertStatus(400);
        $response->assertJson(['error' => 'Missing signature or secret']);
    }

    public function test_handle_fails_with_invalid_signature(): void
    {
        // Arrange
        $webhookSecret = 'whsec_test_secret';
        Config::set('services.stripe.webhook_secret', $webhookSecret);

        $payload = json_encode([
            'id' => 'evt_test123',
            'type' => 'payment_intent.succeeded',
        ]);

        $invalidSignature = 't=' . time() . ',v1=invalid_signature_here';

        Log::shouldReceive('info')->once();
        Log::shouldReceive('error')
            ->once()
            ->with('Stripe webhook processing failed', Mockery::any());

        // Act
        $response = $this->postJson('/api/webhooks/stripe', [], [
            'Stripe-Signature' => $invalidSignature,
        ], [], $payload);

        // Assert
        $response->assertStatus(500);
        $response->assertJsonStructure(['error', 'message']);
    }

    public function test_handle_fails_with_expired_timestamp(): void
    {
        // Arrange
        $webhookSecret = 'whsec_test_secret';
        Config::set('services.stripe.webhook_secret', $webhookSecret);

        $payload = json_encode([
            'id' => 'evt_test123',
            'type' => 'payment_intent.succeeded',
        ]);

        // Generate signature with timestamp from 10 minutes ago (expired)
        $expiredTimestamp = time() - 600;
        $signature = $this->generateValidStripeSignature($payload, $webhookSecret, $expiredTimestamp);

        Log::shouldReceive('info')->once();
        Log::shouldReceive('error')
            ->once()
            ->with('Stripe webhook processing failed', Mockery::any());

        // Act
        $response = $this->postJson('/api/webhooks/stripe', [], [
            'Stripe-Signature' => $signature,
        ], [], $payload);

        // Assert
        $response->assertStatus(500);
        $response->assertJson(['error' => 'Webhook processing failed']);
    }

    public function test_handle_fails_with_invalid_payload_format(): void
    {
        // Arrange
        $webhookSecret = 'whsec_test_secret';
        Config::set('services.stripe.webhook_secret', $webhookSecret);

        $payload = 'invalid json';
        $signature = $this->generateValidStripeSignature($payload, $webhookSecret);

        Log::shouldReceive('info')->once();
        Log::shouldReceive('warning')
            ->once()
            ->with('Invalid Stripe webhook payload');

        // Act
        $response = $this->postJson('/api/webhooks/stripe', [], [
            'Stripe-Signature' => $signature,
        ], [], $payload);

        // Assert
        $response->assertStatus(400);
        $response->assertJson(['error' => 'Invalid payload']);
    }

    public function test_handle_fails_with_payload_missing_type(): void
    {
        // Arrange
        $webhookSecret = 'whsec_test_secret';
        Config::set('services.stripe.webhook_secret', $webhookSecret);

        $payload = json_encode([
            'id' => 'evt_test123',
            // Missing 'type' field
            'data' => [],
        ]);

        $signature = $this->generateValidStripeSignature($payload, $webhookSecret);

        Log::shouldReceive('info')->once();
        Log::shouldReceive('warning')
            ->once()
            ->with('Invalid Stripe webhook payload');

        // Act
        $response = $this->postJson('/api/webhooks/stripe', [], [
            'Stripe-Signature' => $signature,
        ], [], $payload);

        // Assert
        $response->assertStatus(400);
        $response->assertJson(['error' => 'Invalid payload']);
    }

    public function test_handle_logs_webhook_event_details(): void
    {
        // Arrange
        $webhookSecret = 'whsec_test_secret';
        Config::set('services.stripe.webhook_secret', $webhookSecret);

        $payload = json_encode([
            'id' => 'evt_test123',
            'type' => 'charge.refunded',
            'data' => [
                'object' => [
                    'id' => 'ch_test123',
                ],
            ],
        ]);

        $signature = $this->generateValidStripeSignature($payload, $webhookSecret);

        $mockGateway = Mockery::mock(StripePaymentGateway::class);
        $mockGateway->shouldReceive('handleWebhook')->once();
        $this->app->instance(StripePaymentGateway::class, $mockGateway);

        Log::shouldReceive('info')
            ->once()
            ->with('Stripe webhook received', [
                'type' => 'charge.refunded',
                'id' => 'evt_test123',
            ]);

        // Act
        $response = $this->postJson('/api/webhooks/stripe', [], [
            'Stripe-Signature' => $signature,
        ], [], $payload);

        // Assert
        $response->assertStatus(200);
    }

    public function test_handle_supports_multiple_signatures(): void
    {
        // Arrange
        $webhookSecret = 'whsec_test_secret';
        Config::set('services.stripe.webhook_secret', $webhookSecret);

        $payload = json_encode([
            'id' => 'evt_test123',
            'type' => 'payment_intent.succeeded',
        ]);

        $timestamp = time();
        $signedPayload = "{$timestamp}.{$payload}";
        $signature1 = hash_hmac('sha256', $signedPayload, $webhookSecret);
        $signature2 = hash_hmac('sha256', $signedPayload, 'wrong_secret');

        // Multiple signatures (first one is valid)
        $multiSignature = "t={$timestamp},v1={$signature1},v1={$signature2}";

        $mockGateway = Mockery::mock(StripePaymentGateway::class);
        $mockGateway->shouldReceive('handleWebhook')->once();
        $this->app->instance(StripePaymentGateway::class, $mockGateway);

        Log::shouldReceive('info')->once();

        // Act
        $response = $this->postJson('/api/webhooks/stripe', [], [
            'Stripe-Signature' => $multiSignature,
        ], [], $payload);

        // Assert
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    public function test_handle_catches_gateway_exceptions(): void
    {
        // Arrange
        $webhookSecret = 'whsec_test_secret';
        Config::set('services.stripe.webhook_secret', $webhookSecret);

        $payload = json_encode([
            'id' => 'evt_test123',
            'type' => 'payment_intent.succeeded',
        ]);

        $signature = $this->generateValidStripeSignature($payload, $webhookSecret);

        $mockGateway = Mockery::mock(StripePaymentGateway::class);
        $mockGateway->shouldReceive('handleWebhook')
            ->once()
            ->andThrow(new \Exception('Payment processing failed'));

        $this->app->instance(StripePaymentGateway::class, $mockGateway);

        Log::shouldReceive('info')->once();
        Log::shouldReceive('error')
            ->once()
            ->with('Stripe webhook processing failed', Mockery::on(function ($context) {
                return isset($context['error']) && $context['error'] === 'Payment processing failed';
            }));

        // Act
        $response = $this->postJson('/api/webhooks/stripe', [], [
            'Stripe-Signature' => $signature,
        ], [], $payload);

        // Assert
        $response->assertStatus(500);
        $response->assertJson([
            'error' => 'Webhook processing failed',
            'message' => 'Payment processing failed',
        ]);
    }
}
