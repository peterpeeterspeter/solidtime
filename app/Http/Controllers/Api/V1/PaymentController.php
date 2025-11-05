<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\V1\Payment\PaymentIndexRequest;
use App\Http\Requests\V1\Payment\PaymentRefundRequest;
use App\Http\Resources\V1\Payment\PaymentCollection;
use App\Http\Resources\V1\Payment\PaymentResource;
use App\Models\Organization;
use App\Models\Payment;
use App\Services\Payment\PayPalPaymentGateway;
use App\Services\Payment\PaymentGatewayInterface;
use App\Services\Payment\StripePaymentGateway;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;

class PaymentController extends Controller
{
    protected function checkPermission(Organization $organization, string $permission, ?Payment $payment = null): void
    {
        parent::checkPermission($organization, $permission);
        if ($payment !== null && $payment->organization_id !== $organization->getKey()) {
            throw new AuthorizationException('Payment does not belong to organization');
        }
    }

    /**
     * Get payments
     *
     * @return PaymentCollection<PaymentResource>
     *
     * @throws AuthorizationException
     *
     * @operationId getPayments
     */
    public function index(Organization $organization, PaymentIndexRequest $request): PaymentCollection
    {
        $this->checkPermission($organization, 'payments:view');

        $paymentsQuery = Payment::query()
            ->whereBelongsTo($organization, 'organization')
            ->with(['invoice', 'invoice.client'])
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($status = $request->input('status')) {
            if ($status !== 'all') {
                $paymentsQuery->byStatus($status);
            }
        }

        // Filter by invoice
        if ($invoiceId = $request->input('invoice_id')) {
            $paymentsQuery->where('invoice_id', $invoiceId);
        }

        // Filter by gateway
        if ($gateway = $request->input('gateway')) {
            $paymentsQuery->byGateway($gateway);
        }

        // Date range filters
        if ($startDate = $request->input('start_date')) {
            $paymentsQuery->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate = $request->input('end_date')) {
            $paymentsQuery->whereDate('created_at', '<=', $endDate);
        }

        $payments = $paymentsQuery->paginate(config('app.pagination_per_page_default'));

        return new PaymentCollection($payments);
    }

    /**
     * Get a single payment
     *
     * @throws AuthorizationException
     *
     * @operationId getPayment
     */
    public function show(Organization $organization, Payment $payment): PaymentResource
    {
        $this->checkPermission($organization, 'payments:view', $payment);

        $payment->load(['invoice', 'invoice.client', 'paymentGatewayConnection']);

        return new PaymentResource($payment);
    }

    /**
     * Refund a payment
     *
     * @throws AuthorizationException
     *
     * @operationId refundPayment
     */
    public function refund(Organization $organization, Payment $payment, PaymentRefundRequest $request): PaymentResource
    {
        $this->checkPermission($organization, 'payments:refund', $payment);

        // Validate payment can be refunded
        if (!$payment->isCompleted() && !$payment->isPartiallyRefunded()) {
            throw new AuthorizationException('Only completed or partially refunded payments can be refunded');
        }

        $refundAmount = $request->input('amount');
        $refundReason = $request->input('reason');

        // If no amount specified, full refund
        if ($refundAmount === null) {
            $refundAmount = $payment->getRefundableAmount();
        }

        // Validate refund amount
        if ($refundAmount > $payment->getRefundableAmount()) {
            throw new AuthorizationException('Refund amount exceeds refundable amount');
        }

        // Get gateway service and process refund
        $gatewayService = $this->getGatewayService($payment->gateway);
        $updatedPayment = $gatewayService->refundPayment($payment, $refundAmount, $refundReason);

        return new PaymentResource($updatedPayment);
    }

    /**
     * Get the appropriate gateway service instance
     */
    private function getGatewayService(string $gateway): PaymentGatewayInterface
    {
        return match ($gateway) {
            'stripe' => app(StripePaymentGateway::class),
            'paypal' => app(PayPalPaymentGateway::class),
            default => throw new \InvalidArgumentException("Unsupported gateway: {$gateway}"),
        };
    }
}
