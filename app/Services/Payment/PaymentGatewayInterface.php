<?php

namespace App\Services\Payment;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentGatewayConnection;

interface PaymentGatewayInterface
{
    /**
     * Get the gateway name (stripe, paypal, etc.)
     */
    public function getGatewayName(): string;

    /**
     * Initialize OAuth authorization URL for connecting to the gateway
     *
     * @param string $userId
     * @param string $redirectUri
     * @return string Authorization URL
     */
    public function getAuthorizationUrl(string $userId, string $redirectUri): string;

    /**
     * Handle OAuth callback and store connection credentials
     *
     * @param string $code Authorization code from callback
     * @param string $userId
     * @return PaymentGatewayConnection
     */
    public function handleCallback(string $code, string $userId): PaymentGatewayConnection;

    /**
     * Refresh access token if expired
     *
     * @param PaymentGatewayConnection $connection
     * @return PaymentGatewayConnection Updated connection with fresh token
     */
    public function refreshToken(PaymentGatewayConnection $connection): PaymentGatewayConnection;

    /**
     * Create a payment intent/order for an invoice
     *
     * @param Invoice $invoice
     * @param PaymentGatewayConnection $connection
     * @param array $options Additional gateway-specific options
     * @return array Payment intent data (id, client_secret, etc.)
     */
    public function createPaymentIntent(
        Invoice $invoice,
        PaymentGatewayConnection $connection,
        array $options = []
    ): array;

    /**
     * Charge a customer for an invoice
     *
     * @param Invoice $invoice
     * @param PaymentGatewayConnection $connection
     * @param string $paymentMethodId Gateway-specific payment method identifier
     * @param array $options Additional gateway-specific options
     * @return Payment
     */
    public function chargeInvoice(
        Invoice $invoice,
        PaymentGatewayConnection $connection,
        string $paymentMethodId,
        array $options = []
    ): Payment;

    /**
     * Get payment status from gateway
     *
     * @param string $gatewayTransactionId
     * @param PaymentGatewayConnection $connection
     * @return array Payment status data
     */
    public function getPaymentStatus(
        string $gatewayTransactionId,
        PaymentGatewayConnection $connection
    ): array;

    /**
     * Refund a payment
     *
     * @param Payment $payment
     * @param float|null $amount Amount to refund (null = full refund)
     * @param string|null $reason Refund reason
     * @return Payment Updated payment with refund details
     */
    public function refundPayment(
        Payment $payment,
        ?float $amount = null,
        ?string $reason = null
    ): Payment;

    /**
     * Verify webhook signature
     *
     * @param string $payload Raw webhook payload
     * @param string $signature Webhook signature header
     * @param string $secret Webhook secret
     * @return bool
     */
    public function verifyWebhookSignature(
        string $payload,
        string $signature,
        string $secret
    ): bool;

    /**
     * Handle webhook event from payment gateway
     *
     * @param array $event Webhook event data
     * @return void
     */
    public function handleWebhook(array $event): void;

    /**
     * Get customer details from gateway
     *
     * @param string $gatewayCustomerId
     * @param PaymentGatewayConnection $connection
     * @return array Customer data
     */
    public function getCustomer(
        string $gatewayCustomerId,
        PaymentGatewayConnection $connection
    ): array;

    /**
     * Create or update customer in gateway
     *
     * @param array $customerData Customer details (name, email, etc.)
     * @param PaymentGatewayConnection $connection
     * @return string Gateway customer ID
     */
    public function createOrUpdateCustomer(
        array $customerData,
        PaymentGatewayConnection $connection
    ): string;

    /**
     * Disconnect gateway (revoke access, cleanup)
     *
     * @param PaymentGatewayConnection $connection
     * @return bool
     */
    public function disconnect(PaymentGatewayConnection $connection): bool;
}
