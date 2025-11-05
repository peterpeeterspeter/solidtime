<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\Payment\StripePaymentGateway;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Exception;

class StripeWebhookController extends Controller
{
    /**
     * Handle incoming Stripe webhook.
     */
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');
        $webhookSecret = config('services.stripe.webhook_secret');

        if (!$signature || !$webhookSecret) {
            Log::warning('Stripe webhook missing signature or secret');
            return response()->json(['error' => 'Missing signature or secret'], 400);
        }

        try {
            // Verify webhook signature
            $this->verifySignature($payload, $signature, $webhookSecret);

            // Parse event
            $event = json_decode($payload, true);

            if (!$event || !isset($event['type'])) {
                Log::warning('Invalid Stripe webhook payload');
                return response()->json(['error' => 'Invalid payload'], 400);
            }

            Log::info('Stripe webhook received', [
                'type' => $event['type'],
                'id' => $event['id'] ?? null,
            ]);

            // Process event using the gateway service
            $gateway = new StripePaymentGateway();
            $gateway->handleWebhook($event);

            return response()->json(['success' => true]);

        } catch (Exception $e) {
            Log::error('Stripe webhook processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Webhook processing failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Verify Stripe webhook signature.
     *
     * @throws Exception
     */
    private function verifySignature(string $payload, string $signature, string $secret): void
    {
        $tolerance = 300; // 5 minutes

        // Extract timestamp and signatures
        $elements = explode(',', $signature);
        $timestamp = null;
        $signatures = [];

        foreach ($elements as $element) {
            if (strpos($element, 't=') === 0) {
                $timestamp = substr($element, 2);
            } elseif (strpos($element, 'v1=') === 0) {
                $signatures[] = substr($element, 3);
            }
        }

        if (!$timestamp || empty($signatures)) {
            throw new Exception('Invalid Stripe signature format');
        }

        // Check timestamp tolerance
        if (abs(time() - $timestamp) > $tolerance) {
            throw new Exception('Stripe webhook timestamp too old');
        }

        // Verify signature
        $signedPayload = "{$timestamp}.{$payload}";
        $expectedSignature = hash_hmac('sha256', $signedPayload, $secret);

        $isValid = false;
        foreach ($signatures as $sig) {
            if (hash_equals($expectedSignature, $sig)) {
                $isValid = true;
                break;
            }
        }

        if (!$isValid) {
            throw new Exception('Invalid Stripe signature');
        }
    }
}
