<?php

declare(strict_types=1);

namespace App\Http\Resources\V1\Invoice;

use App\Http\Resources\V1\BaseResource;
use App\Http\Resources\V1\Client\ClientResource;
use App\Models\Invoice;
use Illuminate\Http\Request;

/**
 * @property Invoice $resource
 */
class InvoiceResource extends BaseResource
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
            /** @var string $user_id User ID */
            'user_id' => $this->resource->user_id,
            /** @var string $organization_id Organization ID */
            'organization_id' => $this->resource->organization_id,
            /** @var string|null $client_id Client ID */
            'client_id' => $this->resource->client_id,
            /** @var string $invoice_number Invoice number */
            'invoice_number' => $this->resource->invoice_number,
            /** @var string $status Status (draft, sent, paid, overdue, cancelled) */
            'status' => $this->resource->status,
            /** @var string $issue_date Issue date */
            'issue_date' => $this->formatDate($this->resource->issue_date),
            /** @var string $due_date Due date */
            'due_date' => $this->formatDate($this->resource->due_date),
            /** @var string|null $sent_at When the invoice was sent */
            'sent_at' => $this->resource->sent_at
                ? $this->formatDateTime($this->resource->sent_at)
                : null,
            /** @var string|null $paid_at When the invoice was paid */
            'paid_at' => $this->resource->paid_at
                ? $this->formatDateTime($this->resource->paid_at)
                : null,
            /** @var float $subtotal Subtotal amount */
            'subtotal' => (float) $this->resource->subtotal,
            /** @var float $tax_rate Tax rate percentage */
            'tax_rate' => (float) $this->resource->tax_rate,
            /** @var float $tax_amount Tax amount */
            'tax_amount' => (float) $this->resource->tax_amount,
            /** @var float $discount_amount Discount amount */
            'discount_amount' => (float) $this->resource->discount_amount,
            /** @var float $total Total amount */
            'total' => (float) $this->resource->total,
            /** @var string $currency Currency code */
            'currency' => $this->resource->currency,
            /** @var array $from_details Sender details */
            'from_details' => $this->resource->from_details,
            /** @var array $to_details Recipient details */
            'to_details' => $this->resource->to_details,
            /** @var array $line_items Line items */
            'line_items' => $this->resource->line_items,
            /** @var string|null $notes Notes */
            'notes' => $this->resource->notes,
            /** @var string|null $terms Terms and conditions */
            'terms' => $this->resource->terms,
            /** @var string|null $payment_method Payment method */
            'payment_method' => $this->resource->payment_method,
            /** @var string|null $payment_instructions Payment instructions */
            'payment_instructions' => $this->resource->payment_instructions,
            /** @var array|null $metadata Additional metadata */
            'metadata' => $this->resource->metadata,
            /** @var string $created_at When the invoice was created */
            'created_at' => $this->formatDateTime($this->resource->created_at),
            /** @var string $updated_at When the invoice was last updated */
            'updated_at' => $this->formatDateTime($this->resource->updated_at),
            /** @var ClientResource|null $client Client relationship */
            'client' => $this->whenLoaded('client', fn () => new ClientResource($this->resource->client)),
            /** @var float $total_paid Total amount paid */
            'total_paid' => $this->resource->getTotalPaid(),
            /** @var float $remaining_balance Remaining balance */
            'remaining_balance' => $this->resource->getRemainingBalance(),
        ];
    }
}
