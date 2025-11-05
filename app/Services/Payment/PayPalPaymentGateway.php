<?php

namespace App\Services\Payment;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentGatewayConnection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class PayPalPaymentGateway implements PaymentGatewayInterface
{
    private const API_BASE_URL = 'https://api-m.paypal.com';
    private const SANDBOX_API_BASE_URL = 'https://api-m.sandbox.paypal.com';

    /**
     * Get the gateway name.
     */
    public function getGatewayName(): string
    {
        return 'paypal';
    }

    /**
     * Get PayPal OAuth authorization URL.
     */
    public function getAuthorizationUrl(string $userId, string $redirectUri): string
    {
        $clientId = config('services.paypal.client_id');
        $isSandbox = config('services.paypal.sandbox', false);
        $baseUrl = $isSandbox ? 'https://www.sandbox.paypal.com' : 'https://www.paypal.com';
        $state = encrypt(['user_id' => $userId, 'timestamp' => now()->timestamp]);

        $params = http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'openid profile email https://uri.paypal.com/services/invoicing',
            'state' => $state,
        ]);

        return "{$baseUrl}/connect?{$params}";
    }

    /**
     * Handle PayPal OAuth callback.
     */
    public function handleCallback(string $code, string $userId): PaymentGatewayConnection
    {
        $baseUrl = $this->getApiBaseUrl();
        $clientId = config('services.paypal.client_id');
        $secret = config('services.paypal.secret');

        $response = Http::withBasicAuth($clientId, $secret)
            ->asForm()
            ->post("{$baseUrl}/v1/oauth2/token", [
                'grant_type' => 'authorization_code',
                'code' => $code,
            ]);

        if (!$response->successful()) {
            throw new Exception('Failed to obtain PayPal access token: ' . $response->body());
        }

        $data = $response->json();

        // Get user info to store account ID
        $userInfo = $this->getUserInfo($data['access_token']);

        $connection = new PaymentGatewayConnection([
            'user_id' => $userId,
            'gateway' => 'paypal',
            'gateway_account_id' => $userInfo['user_id'] ?? $userInfo['payer_id'] ?? null,
            'is_active' => true,
            'token_expires_at' => now()->addSeconds($data['expires_in']),
            'metadata' => [
                'scope' => $data['scope'] ?? null,
                'email' => $userInfo['email'] ?? null,
            ],
        ]);

        $connection->setAccessToken($data['access_token']);

        if (isset($data['refresh_token'])) {
            $connection->setRefreshToken($data['refresh_token']);
        }

        $connection->save();

        return $connection;
    }

    /**
     * Refresh PayPal access token.
     */
    public function refreshToken(PaymentGatewayConnection $connection): PaymentGatewayConnection
    {
        $refreshToken = $connection->getDecryptedRefreshToken();

        if (!$refreshToken) {
            throw new Exception('No refresh token available for PayPal connection');
        }

        $baseUrl = $this->getApiBaseUrl();
        $clientId = config('services.paypal.client_id');
        $secret = config('services.paypal.secret');

        $response = Http::withBasicAuth($clientId, $secret)
            ->asForm()
            ->post("{$baseUrl}/v1/oauth2/token", [
                'grant_type' => 'refresh_token',
                'refresh_token' => $refreshToken,
            ]);

        if (!$response->successful()) {
            throw new Exception('Failed to refresh PayPal token: ' . $response->body());
        }

        $data = $response->json();

        $connection->setAccessToken($data['access_token']);
        $connection->token_expires_at = now()->addSeconds($data['expires_in']);
        $connection->save();

        return $connection;
    }

    /**
     * Create a PayPal order (payment intent).
     */
    public function createPaymentIntent(
        Invoice $invoice,
        PaymentGatewayConnection $connection,
        array $options = []
    ): array {
        $accessToken = $this->getValidAccessToken($connection);
        $baseUrl = $this->getApiBaseUrl();

        $response = Http::withToken($accessToken)
            ->post("{$baseUrl}/v2/checkout/orders", [
                'intent' => 'CAPTURE',
                'purchase_units' => [
                    [
                        'reference_id' => $invoice->invoice_number,
                        'description' => "Invoice {$invoice->invoice_number}",
                        'amount' => [
                            'currency_code' => strtoupper($invoice->currency),
                            'value' => number_format($invoice->total, 2, '.', ''),
                        ],
                        'custom_id' => $invoice->id,
                    ]
                ],
                'application_context' => [
                    'brand_name' => config('app.name'),
                    'return_url' => $options['return_url'] ?? url('/payments/paypal/return'),
                    'cancel_url' => $options['cancel_url'] ?? url('/payments/paypal/cancel'),
                ],
            ]);

        if (!$response->successful()) {
            throw new Exception('Failed to create PayPal order: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Charge an invoice via PayPal.
     */
    public function chargeInvoice(
        Invoice $invoice,
        PaymentGatewayConnection $connection,
        string $paymentMethodId,
        array $options = []
    ): Payment {
        $accessToken = $this->getValidAccessToken($connection);
        $baseUrl = $this->getApiBaseUrl();

        // Capture the order
        $response = Http::withToken($accessToken)
            ->post("{$baseUrl}/v2/checkout/orders/{$paymentMethodId}/capture");

        if (!$response->successful()) {
            throw new Exception('Failed to capture PayPal order: ' . $response->body());
        }

        $orderData = $response->json();

        // Create payment record
        $payment = new Payment([
            'invoice_id' => $invoice->id,
            'user_id' => $invoice->user_id,
            'organization_id' => $invoice->organization_id,
            'payment_gateway_connection_id' => $connection->id,
            'gateway' => 'paypal',
            'gateway_transaction_id' => $orderData['id'],
            'gateway_payment_method_id' => $paymentMethodId,
            'amount' => $invoice->total,
            'currency' => $invoice->currency,
            'status' => $this->mapPayPalStatus($orderData['status']),
            'gateway_response' => $orderData,
        ]);

        // Calculate fees from capture details
        if (isset($orderData['purchase_units'][0]['payments']['captures'][0])) {
            $capture = $orderData['purchase_units'][0]['payments']['captures'][0];

            if (isset($capture['seller_receivable_breakdown'])) {
                $breakdown = $capture['seller_receivable_breakdown'];
                $payment->fee_amount = isset($breakdown['paypal_fee'])
                    ? (float) $breakdown['paypal_fee']['value']
                    : 0;
                $payment->net_amount = isset($breakdown['net_amount'])
                    ? (float) $breakdown['net_amount']['value']
                    : $payment->amount;
            } else {
                $payment->net_amount = $payment->amount;
            }
        } else {
            $payment->net_amount = $payment->amount;
        }

        if ($payment->status === 'completed') {
            $payment->paid_at = now();
        }

        $payment->save();

        return $payment;
    }

    /**
     * Get payment status from PayPal.
     */
    public function getPaymentStatus(
        string $gatewayTransactionId,
        PaymentGatewayConnection $connection
    ): array {
        $accessToken = $this->getValidAccessToken($connection);
        $baseUrl = $this->getApiBaseUrl();

        $response = Http::withToken($accessToken)
            ->get("{$baseUrl}/v2/checkout/orders/{$gatewayTransactionId}");

        if (!$response->successful()) {
            throw new Exception('Failed to get PayPal order status: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Refund a PayPal payment.
     */
    public function refundPayment(
        Payment $payment,
        ?float $amount = null,
        ?string $reason = null
    ): Payment {
        if (!$payment->paymentGatewayConnection) {
            throw new Exception('Payment gateway connection not found');
        }

        $accessToken = $this->getValidAccessToken($payment->paymentGatewayConnection);
        $baseUrl = $this->getApiBaseUrl();

        // Get capture ID from payment response
        $captureId = $this->extractCaptureId($payment->gateway_response);

        if (!$captureId) {
            throw new Exception('Capture ID not found in payment response');
        }

        $refundData = [];

        if ($amount !== null) {
            $refundData['amount'] = [
                'value' => number_format($amount, 2, '.', ''),
                'currency_code' => strtoupper($payment->currency),
            ];
        }

        if ($reason) {
            $refundData['note_to_payer'] = $reason;
        }

        $response = Http::withToken($accessToken)
            ->post("{$baseUrl}/v2/payments/captures/{$captureId}/refund", $refundData);

        if (!$response->successful()) {
            throw new Exception('Failed to refund PayPal payment: ' . $response->body());
        }

        $refundInfo = $response->json();

        $refundAmount = $amount ?? $payment->amount;
        $totalRefunded = $payment->refund_amount + $refundAmount;

        $payment->update([
            'refund_amount' => $totalRefunded,
            'refund_reason' => $reason,
            'refund_transaction_id' => $refundInfo['id'],
            'refunded_at' => now(),
            'status' => $totalRefunded >= $payment->amount ? 'refunded' : 'partially_refunded',
        ]);

        return $payment;
    }

    /**
     * Verify PayPal webhook signature.
     */
    public function verifyWebhookSignature(
        string $payload,
        string $signature,
        string $secret
    ): bool {
        $baseUrl = $this->getApiBaseUrl();

        // Get access token for verification
        $clientId = config('services.paypal.client_id');
        $clientSecret = config('services.paypal.secret');

        $tokenResponse = Http::withBasicAuth($clientId, $clientSecret)
            ->asForm()
            ->post("{$baseUrl}/v1/oauth2/token", [
                'grant_type' => 'client_credentials',
            ]);

        if (!$tokenResponse->successful()) {
            return false;
        }

        $accessToken = $tokenResponse->json()['access_token'];

        // Verify webhook signature
        $response = Http::withToken($accessToken)
            ->post("{$baseUrl}/v1/notifications/verify-webhook-signature", [
                'transmission_id' => request()->header('PAYPAL-TRANSMISSION-ID'),
                'transmission_time' => request()->header('PAYPAL-TRANSMISSION-TIME'),
                'cert_url' => request()->header('PAYPAL-CERT-URL'),
                'auth_algo' => request()->header('PAYPAL-AUTH-ALGO'),
                'transmission_sig' => $signature,
                'webhook_id' => $secret, // Webhook ID is used as secret
                'webhook_event' => json_decode($payload, true),
            ]);

        if (!$response->successful()) {
            return false;
        }

        $verification = $response->json();
        return ($verification['verification_status'] ?? '') === 'SUCCESS';
    }

    /**
     * Handle PayPal webhook event.
     */
    public function handleWebhook(array $event): void
    {
        $type = $event['event_type'] ?? null;

        Log::info('PayPal webhook received', ['type' => $type, 'event_id' => $event['id'] ?? null]);

        switch ($type) {
            case 'PAYMENT.CAPTURE.COMPLETED':
                $this->handlePaymentCaptureCompleted($event['resource']);
                break;

            case 'PAYMENT.CAPTURE.DENIED':
            case 'PAYMENT.CAPTURE.DECLINED':
                $this->handlePaymentCaptureFailed($event['resource']);
                break;

            case 'PAYMENT.CAPTURE.REFUNDED':
                $this->handlePaymentCaptureRefunded($event['resource']);
                break;

            default:
                Log::info('Unhandled PayPal webhook type', ['type' => $type]);
        }
    }

    /**
     * Get customer from PayPal.
     */
    public function getCustomer(
        string $gatewayCustomerId,
        PaymentGatewayConnection $connection
    ): array {
        // PayPal doesn't have a traditional customer object like Stripe
        // Return basic info from connection metadata
        return $connection->metadata ?? [];
    }

    /**
     * Create or update customer in PayPal.
     */
    public function createOrUpdateCustomer(
        array $customerData,
        PaymentGatewayConnection $connection
    ): string {
        // PayPal doesn't require pre-creating customers
        // Return a placeholder ID
        return $connection->gateway_account_id;
    }

    /**
     * Disconnect PayPal account.
     */
    public function disconnect(PaymentGatewayConnection $connection): bool
    {
        try {
            $connection->update(['is_active' => false]);
            return true;
        } catch (Exception $e) {
            Log::error('Failed to disconnect PayPal account', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Get API base URL based on environment.
     */
    private function getApiBaseUrl(): string
    {
        return config('services.paypal.sandbox', false)
            ? self::SANDBOX_API_BASE_URL
            : self::API_BASE_URL;
    }

    /**
     * Get valid access token, refreshing if needed.
     */
    private function getValidAccessToken(PaymentGatewayConnection $connection): string
    {
        if ($connection->isTokenExpired()) {
            $connection = $this->refreshToken($connection);
        }

        return $connection->getDecryptedAccessToken();
    }

    /**
     * Get user info from PayPal.
     */
    private function getUserInfo(string $accessToken): array
    {
        $baseUrl = $this->getApiBaseUrl();

        $response = Http::withToken($accessToken)
            ->get("{$baseUrl}/v1/identity/oauth2/userinfo", [
                'schema' => 'paypalv1.1',
            ]);

        if (!$response->successful()) {
            return [];
        }

        return $response->json();
    }

    /**
     * Extract capture ID from PayPal order response.
     */
    private function extractCaptureId(array $orderData): ?string
    {
        if (isset($orderData['purchase_units'][0]['payments']['captures'][0]['id'])) {
            return $orderData['purchase_units'][0]['payments']['captures'][0]['id'];
        }

        return null;
    }

    /**
     * Map PayPal order status to internal status.
     */
    private function mapPayPalStatus(string $paypalStatus): string
    {
        return match ($paypalStatus) {
            'COMPLETED' => 'completed',
            'APPROVED' => 'processing',
            'CREATED', 'SAVED', 'PAYER_ACTION_REQUIRED' => 'pending',
            'VOIDED', 'DECLINED' => 'failed',
            default => 'pending',
        };
    }

    /**
     * Handle completed payment capture.
     */
    private function handlePaymentCaptureCompleted(array $capture): void
    {
        $customId = $capture['custom_id'] ?? null;

        if (!$customId) {
            return;
        }

        $payment = Payment::where('invoice_id', $customId)
            ->where('gateway', 'paypal')
            ->first();

        if ($payment) {
            $payment->update([
                'status' => 'completed',
                'paid_at' => now(),
                'gateway_response' => $capture,
            ]);

            // Mark invoice as paid
            $payment->invoice->markAsPaid();
        }
    }

    /**
     * Handle failed payment capture.
     */
    private function handlePaymentCaptureFailed(array $capture): void
    {
        $payment = Payment::where('gateway_transaction_id', $capture['id'])->first();

        if ($payment) {
            $payment->update([
                'status' => 'failed',
                'failed_at' => now(),
                'status_message' => $capture['status_details']['reason'] ?? 'Payment failed',
                'gateway_response' => $capture,
            ]);
        }
    }

    /**
     * Handle payment capture refund.
     */
    private function handlePaymentCaptureRefunded(array $capture): void
    {
        $payment = Payment::where('gateway_transaction_id', $capture['id'])->first();

        if ($payment) {
            $refundAmount = isset($capture['amount'])
                ? (float) $capture['amount']['value']
                : $payment->amount;

            $payment->update([
                'refund_amount' => $refundAmount,
                'refunded_at' => now(),
                'status' => $refundAmount >= $payment->amount ? 'refunded' : 'partially_refunded',
            ]);
        }
    }
}
