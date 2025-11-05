<?php

namespace App\Services\Payment;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentGatewayConnection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class StripePaymentGateway implements PaymentGatewayInterface
{
    private const API_BASE_URL = 'https://api.stripe.com/v1';
    private const OAUTH_URL = 'https://connect.stripe.com/oauth';

    /**
     * Get the gateway name.
     */
    public function getGatewayName(): string
    {
        return 'stripe';
    }

    /**
     * Get Stripe OAuth authorization URL.
     */
    public function getAuthorizationUrl(string $userId, string $redirectUri): string
    {
        $clientId = config('services.stripe.client_id');
        $state = encrypt(['user_id' => $userId, 'timestamp' => now()->timestamp]);

        $params = http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'read_write',
            'state' => $state,
        ]);

        return self::OAUTH_URL . "/authorize?{$params}";
    }

    /**
     * Handle Stripe OAuth callback.
     */
    public function handleCallback(string $code, string $userId): PaymentGatewayConnection
    {
        $response = Http::asForm()->post(self::OAUTH_URL . '/token', [
            'client_secret' => config('services.stripe.secret'),
            'code' => $code,
            'grant_type' => 'authorization_code',
        ]);

        if (!$response->successful()) {
            throw new Exception('Failed to obtain Stripe access token: ' . $response->body());
        }

        $data = $response->json();

        $connection = new PaymentGatewayConnection([
            'user_id' => $userId,
            'gateway' => 'stripe',
            'gateway_account_id' => $data['stripe_user_id'],
            'is_active' => true,
            'metadata' => [
                'stripe_publishable_key' => $data['stripe_publishable_key'] ?? null,
                'scope' => $data['scope'] ?? null,
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
     * Refresh Stripe access token.
     */
    public function refreshToken(PaymentGatewayConnection $connection): PaymentGatewayConnection
    {
        $refreshToken = $connection->getDecryptedRefreshToken();

        if (!$refreshToken) {
            throw new Exception('No refresh token available for Stripe connection');
        }

        $response = Http::asForm()->post(self::OAUTH_URL . '/token', [
            'client_secret' => config('services.stripe.secret'),
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token',
        ]);

        if (!$response->successful()) {
            throw new Exception('Failed to refresh Stripe token: ' . $response->body());
        }

        $data = $response->json();

        $connection->setAccessToken($data['access_token']);

        if (isset($data['refresh_token'])) {
            $connection->setRefreshToken($data['refresh_token']);
        }

        $connection->save();

        return $connection;
    }

    /**
     * Create a Stripe payment intent.
     */
    public function createPaymentIntent(
        Invoice $invoice,
        PaymentGatewayConnection $connection,
        array $options = []
    ): array {
        $accessToken = $this->getValidAccessToken($connection);

        $response = Http::withToken($accessToken)
            ->asForm()
            ->post(self::API_BASE_URL . '/payment_intents', [
                'amount' => $this->convertToStripeAmount($invoice->total, $invoice->currency),
                'currency' => strtolower($invoice->currency),
                'description' => "Invoice {$invoice->invoice_number}",
                'metadata' => [
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                ],
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
                ...$options,
            ]);

        if (!$response->successful()) {
            throw new Exception('Failed to create Stripe payment intent: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Charge an invoice via Stripe.
     */
    public function chargeInvoice(
        Invoice $invoice,
        PaymentGatewayConnection $connection,
        string $paymentMethodId,
        array $options = []
    ): Payment {
        $accessToken = $this->getValidAccessToken($connection);

        // Create payment intent
        $response = Http::withToken($accessToken)
            ->asForm()
            ->post(self::API_BASE_URL . '/payment_intents', [
                'amount' => $this->convertToStripeAmount($invoice->total, $invoice->currency),
                'currency' => strtolower($invoice->currency),
                'payment_method' => $paymentMethodId,
                'confirm' => true,
                'description' => "Invoice {$invoice->invoice_number}",
                'metadata' => [
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                ],
                ...$options,
            ]);

        if (!$response->successful()) {
            throw new Exception('Failed to charge Stripe payment: ' . $response->body());
        }

        $intentData = $response->json();

        // Create payment record
        $payment = new Payment([
            'invoice_id' => $invoice->id,
            'user_id' => $invoice->user_id,
            'organization_id' => $invoice->organization_id,
            'payment_gateway_connection_id' => $connection->id,
            'gateway' => 'stripe',
            'gateway_transaction_id' => $intentData['id'],
            'gateway_payment_method_id' => $paymentMethodId,
            'amount' => $invoice->total,
            'currency' => $invoice->currency,
            'status' => $this->mapStripeStatus($intentData['status']),
            'gateway_response' => $intentData,
        ]);

        // Calculate fees and net amount
        if (isset($intentData['charges']['data'][0])) {
            $charge = $intentData['charges']['data'][0];
            $payment->fee_amount = $this->convertFromStripeAmount(
                $charge['application_fee_amount'] ?? 0,
                $invoice->currency
            );
            $payment->net_amount = $payment->amount - $payment->fee_amount;
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
     * Get payment status from Stripe.
     */
    public function getPaymentStatus(
        string $gatewayTransactionId,
        PaymentGatewayConnection $connection
    ): array {
        $accessToken = $this->getValidAccessToken($connection);

        $response = Http::withToken($accessToken)
            ->get(self::API_BASE_URL . "/payment_intents/{$gatewayTransactionId}");

        if (!$response->successful()) {
            throw new Exception('Failed to get Stripe payment status: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Refund a Stripe payment.
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

        $refundData = [
            'payment_intent' => $payment->gateway_transaction_id,
        ];

        if ($amount !== null) {
            $refundData['amount'] = $this->convertToStripeAmount($amount, $payment->currency);
        }

        if ($reason) {
            $refundData['reason'] = $reason;
        }

        $response = Http::withToken($accessToken)
            ->asForm()
            ->post(self::API_BASE_URL . '/refunds', $refundData);

        if (!$response->successful()) {
            throw new Exception('Failed to refund Stripe payment: ' . $response->body());
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
     * Verify Stripe webhook signature.
     */
    public function verifyWebhookSignature(
        string $payload,
        string $signature,
        string $secret
    ): bool {
        $signedPayload = "{$payload}.{$secret}";
        $expectedSignature = hash_hmac('sha256', $signedPayload, $secret);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Handle Stripe webhook event.
     */
    public function handleWebhook(array $event): void
    {
        $type = $event['type'] ?? null;

        Log::info('Stripe webhook received', ['type' => $type, 'event_id' => $event['id'] ?? null]);

        switch ($type) {
            case 'payment_intent.succeeded':
                $this->handlePaymentIntentSucceeded($event['data']['object']);
                break;

            case 'payment_intent.payment_failed':
                $this->handlePaymentIntentFailed($event['data']['object']);
                break;

            case 'charge.refunded':
                $this->handleChargeRefunded($event['data']['object']);
                break;

            default:
                Log::info('Unhandled Stripe webhook type', ['type' => $type]);
        }
    }

    /**
     * Get customer from Stripe.
     */
    public function getCustomer(
        string $gatewayCustomerId,
        PaymentGatewayConnection $connection
    ): array {
        $accessToken = $this->getValidAccessToken($connection);

        $response = Http::withToken($accessToken)
            ->get(self::API_BASE_URL . "/customers/{$gatewayCustomerId}");

        if (!$response->successful()) {
            throw new Exception('Failed to get Stripe customer: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Create or update customer in Stripe.
     */
    public function createOrUpdateCustomer(
        array $customerData,
        PaymentGatewayConnection $connection
    ): string {
        $accessToken = $this->getValidAccessToken($connection);

        $response = Http::withToken($accessToken)
            ->asForm()
            ->post(self::API_BASE_URL . '/customers', $customerData);

        if (!$response->successful()) {
            throw new Exception('Failed to create Stripe customer: ' . $response->body());
        }

        $customer = $response->json();

        return $customer['id'];
    }

    /**
     * Disconnect Stripe account.
     */
    public function disconnect(PaymentGatewayConnection $connection): bool
    {
        try {
            $response = Http::asForm()->post(self::OAUTH_URL . '/deauthorize', [
                'client_id' => config('services.stripe.client_id'),
                'stripe_user_id' => $connection->gateway_account_id,
            ]);

            $connection->update(['is_active' => false]);

            return $response->successful();
        } catch (Exception $e) {
            Log::error('Failed to disconnect Stripe account', ['error' => $e->getMessage()]);
            return false;
        }
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
     * Convert amount to Stripe format (cents).
     */
    private function convertToStripeAmount(float $amount, string $currency): int
    {
        // Zero-decimal currencies don't need conversion
        $zeroDecimalCurrencies = ['BIF', 'CLP', 'DJF', 'GNF', 'JPY', 'KMF', 'KRW', 'MGA', 'PYG', 'RWF', 'UGX', 'VND', 'VUV', 'XAF', 'XOF', 'XPF'];

        if (in_array(strtoupper($currency), $zeroDecimalCurrencies)) {
            return (int) $amount;
        }

        return (int) ($amount * 100);
    }

    /**
     * Convert amount from Stripe format.
     */
    private function convertFromStripeAmount(int $amount, string $currency): float
    {
        $zeroDecimalCurrencies = ['BIF', 'CLP', 'DJF', 'GNF', 'JPY', 'KMF', 'KRW', 'MGA', 'PYG', 'RWF', 'UGX', 'VND', 'VUV', 'XAF', 'XOF', 'XPF'];

        if (in_array(strtoupper($currency), $zeroDecimalCurrencies)) {
            return (float) $amount;
        }

        return $amount / 100;
    }

    /**
     * Map Stripe payment status to internal status.
     */
    private function mapStripeStatus(string $stripeStatus): string
    {
        return match ($stripeStatus) {
            'succeeded' => 'completed',
            'processing' => 'processing',
            'requires_payment_method', 'requires_confirmation', 'requires_action' => 'pending',
            'canceled' => 'failed',
            default => 'pending',
        };
    }

    /**
     * Handle successful payment intent.
     */
    private function handlePaymentIntentSucceeded(array $paymentIntent): void
    {
        $invoiceId = $paymentIntent['metadata']['invoice_id'] ?? null;

        if (!$invoiceId) {
            return;
        }

        $payment = Payment::where('gateway_transaction_id', $paymentIntent['id'])->first();

        if ($payment) {
            $payment->update([
                'status' => 'completed',
                'paid_at' => now(),
                'gateway_response' => $paymentIntent,
            ]);

            // Mark invoice as paid
            $payment->invoice->markAsPaid();
        }
    }

    /**
     * Handle failed payment intent.
     */
    private function handlePaymentIntentFailed(array $paymentIntent): void
    {
        $payment = Payment::where('gateway_transaction_id', $paymentIntent['id'])->first();

        if ($payment) {
            $payment->update([
                'status' => 'failed',
                'failed_at' => now(),
                'status_message' => $paymentIntent['last_payment_error']['message'] ?? 'Payment failed',
                'gateway_response' => $paymentIntent,
            ]);
        }
    }

    /**
     * Handle charge refund.
     */
    private function handleChargeRefunded(array $charge): void
    {
        // Payment intent ID is available in charge
        $paymentIntentId = $charge['payment_intent'] ?? null;

        if (!$paymentIntentId) {
            return;
        }

        $payment = Payment::where('gateway_transaction_id', $paymentIntentId)->first();

        if ($payment) {
            $refundAmount = $this->convertFromStripeAmount($charge['amount_refunded'], $payment->currency);

            $payment->update([
                'refund_amount' => $refundAmount,
                'refunded_at' => now(),
                'status' => $refundAmount >= $payment->amount ? 'refunded' : 'partially_refunded',
            ]);
        }
    }
}
