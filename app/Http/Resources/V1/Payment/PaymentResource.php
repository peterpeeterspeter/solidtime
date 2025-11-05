<?php

declare(strict_types=1);

namespace App\Http\Resources\V1\Payment;

use App\Http\Resources\V1\BaseResource;
use App\Http\Resources\V1\Invoice\InvoiceResource;
use App\Models\Payment;
use Illuminate\Http\Request;

/**
 * @property Payment $resource
 */
class PaymentResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, string|bool|int|float|null|array>
     */
    public function toArray(Request $request): array
    {
        return [
            /** @var string $id ID */
            'id' => $this->resource->id,
            /** @var string $invoice_id Invoice ID */
            'invoice_id' => $this->resource->invoice_id,
            /** @var string $user_id User ID */
            'user_id' => $this->resource->user_id,
            /** @var string $organization_id Organization ID */
            'organization_id' => $this->resource->organization_id,
            /** @var string|null $payment_gateway_connection_id Payment gateway connection ID */
            'payment_gateway_connection_id' => $this->resource->payment_gateway_connection_id,
            /** @var string $gateway Gateway type (stripe, paypal) */
            'gateway' => $this->resource->gateway,
            /** @var string|null $gateway_transaction_id Gateway transaction ID */
            'gateway_transaction_id' => $this->resource->gateway_transaction_id,
            /** @var string|null $gateway_payment_method_id Gateway payment method ID */
            'gateway_payment_method_id' => $this->resource->gateway_payment_method_id,
            /** @var float $amount Payment amount */
            'amount' => (float) $this->resource->amount,
            /** @var string $currency Currency code */
            'currency' => $this->resource->currency,
            /** @var float $fee_amount Fee amount */
            'fee_amount' => (float) $this->resource->fee_amount,
            /** @var float $net_amount Net amount */
            'net_amount' => (float) $this->resource->net_amount,
            /** @var string $status Status (pending, processing, completed, failed, refunded, partially_refunded) */
            'status' => $this->resource->status,
            /** @var string|null $status_message Status message */
            'status_message' => $this->resource->status_message,
            /** @var string|null $paid_at When the payment was completed */
            'paid_at' => $this->resource->paid_at
                ? $this->formatDateTime($this->resource->paid_at)
                : null,
            /** @var string|null $failed_at When the payment failed */
            'failed_at' => $this->resource->failed_at
                ? $this->formatDateTime($this->resource->failed_at)
                : null,
            /** @var string|null $refunded_at When the payment was refunded */
            'refunded_at' => $this->resource->refunded_at
                ? $this->formatDateTime($this->resource->refunded_at)
                : null,
            /** @var float $refund_amount Refund amount */
            'refund_amount' => (float) $this->resource->refund_amount,
            /** @var string|null $refund_reason Refund reason */
            'refund_reason' => $this->resource->refund_reason,
            /** @var string|null $refund_transaction_id Refund transaction ID */
            'refund_transaction_id' => $this->resource->refund_transaction_id,
            /** @var array|null $customer_details Customer details */
            'customer_details' => $this->resource->customer_details,
            /** @var array|null $gateway_response Gateway response */
            'gateway_response' => $this->resource->gateway_response,
            /** @var array|null $metadata Additional metadata */
            'metadata' => $this->resource->metadata,
            /** @var string $created_at When the payment was created */
            'created_at' => $this->formatDateTime($this->resource->created_at),
            /** @var string $updated_at When the payment was last updated */
            'updated_at' => $this->formatDateTime($this->resource->updated_at),
            /** @var InvoiceResource|null $invoice Invoice relationship */
            'invoice' => $this->whenLoaded('invoice', fn () => new InvoiceResource($this->resource->invoice)),
            /** @var float $refundable_amount Remaining refundable amount */
            'refundable_amount' => $this->resource->getRefundableAmount(),
        ];
    }
}
