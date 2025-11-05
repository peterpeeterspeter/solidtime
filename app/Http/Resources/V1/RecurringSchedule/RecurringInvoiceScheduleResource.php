<?php

declare(strict_types=1);

namespace App\Http\Resources\V1\RecurringSchedule;

use App\Http\Resources\V1\BaseResource;
use App\Http\Resources\V1\Client\ClientResource;
use App\Models\RecurringInvoiceSchedule;
use Illuminate\Http\Request;

/**
 * @property RecurringInvoiceSchedule $resource
 */
class RecurringInvoiceScheduleResource extends BaseResource
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
            /** @var string|null $project_id Project ID */
            'project_id' => $this->resource->project_id,
            /** @var string $name Schedule name */
            'name' => $this->resource->name,
            /** @var string $frequency Frequency (daily, weekly, monthly, etc.) */
            'frequency' => $this->resource->frequency,
            /** @var int $interval Interval */
            'interval' => $this->resource->interval,
            /** @var int|null $day_of_month Day of month */
            'day_of_month' => $this->resource->day_of_month,
            /** @var int|null $day_of_week Day of week */
            'day_of_week' => $this->resource->day_of_week,
            /** @var string $start_date Start date */
            'start_date' => $this->formatDate($this->resource->start_date),
            /** @var string|null $end_date End date */
            'end_date' => $this->resource->end_date
                ? $this->formatDate($this->resource->end_date)
                : null,
            /** @var string|null $next_generation_date Next generation date */
            'next_generation_date' => $this->resource->next_generation_date
                ? $this->formatDate($this->resource->next_generation_date)
                : null,
            /** @var string|null $last_generated_date Last generated date */
            'last_generated_date' => $this->resource->last_generated_date
                ? $this->formatDate($this->resource->last_generated_date)
                : null,
            /** @var int|null $max_occurrences Maximum occurrences */
            'max_occurrences' => $this->resource->max_occurrences,
            /** @var int $occurrences_count Current occurrences count */
            'occurrences_count' => $this->resource->occurrences_count,
            /** @var string $status Status (active, paused, completed) */
            'status' => $this->resource->status,
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
            /** @var int $due_days Due days */
            'due_days' => $this->resource->due_days,
            /** @var string $due_date_type Due date type */
            'due_date_type' => $this->resource->due_date_type,
            /** @var bool $auto_send Auto send */
            'auto_send' => $this->resource->auto_send,
            /** @var bool $auto_charge Auto charge */
            'auto_charge' => $this->resource->auto_charge,
            /** @var bool $include_time_entries Include time entries */
            'include_time_entries' => $this->resource->include_time_entries,
            /** @var string|null $time_entries_from_date Time entries from date */
            'time_entries_from_date' => $this->resource->time_entries_from_date
                ? $this->formatDate($this->resource->time_entries_from_date)
                : null,
            /** @var string|null $time_entries_to_date Time entries to date */
            'time_entries_to_date' => $this->resource->time_entries_to_date
                ? $this->formatDate($this->resource->time_entries_to_date)
                : null,
            /** @var bool $notify_on_generation Notify on generation */
            'notify_on_generation' => $this->resource->notify_on_generation,
            /** @var array|null $notification_emails Notification emails */
            'notification_emails' => $this->resource->notification_emails,
            /** @var array|null $metadata Additional metadata */
            'metadata' => $this->resource->metadata,
            /** @var string $created_at When the schedule was created */
            'created_at' => $this->formatDateTime($this->resource->created_at),
            /** @var string $updated_at When the schedule was last updated */
            'updated_at' => $this->formatDateTime($this->resource->updated_at),
            /** @var ClientResource|null $client Client relationship */
            'client' => $this->whenLoaded('client', fn () => new ClientResource($this->resource->client)),
        ];
    }
}
