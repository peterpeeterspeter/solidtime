<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\Payment\PayPalPaymentGateway;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Exception;

class PayPalWebhookController extends Controller
{
    /**
     * Handle incoming PayPal webhook.
     */
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $signature = $request->header('PAYPAL-TRANSMISSION-SIG');
        $webhookId = config('services.paypal.webhook_id');

        if (!$signature || !$webhookId) {
            Log::warning('PayPal webhook missing signature or webhook ID');
            return response()->json(['error' => 'Missing signature or webhook ID'], 400);
        }

        try {
            // Verify webhook signature
            $gateway = new PayPalPaymentGateway();
            $isValid = $gateway->verifyWebhookSignature($payload, $signature, $webhookId);

            if (!$isValid) {
                Log::warning('PayPal webhook signature verification failed');
                return response()->json(['error' => 'Invalid signature'], 401);
            }

            // Parse event
            $event = json_decode($payload, true);

            if (!$event || !isset($event['event_type'])) {
                Log::warning('Invalid PayPal webhook payload');
                return response()->json(['error' => 'Invalid payload'], 400);
            }

            Log::info('PayPal webhook received', [
                'type' => $event['event_type'],
                'id' => $event['id'] ?? null,
            ]);

            // Process event using the gateway service
            $gateway->handleWebhook($event);

            return response()->json(['success' => true]);

        } catch (Exception $e) {
            Log::error('PayPal webhook processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Webhook processing failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
