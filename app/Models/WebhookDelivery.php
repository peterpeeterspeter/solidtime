<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * WebhookDelivery Model
 *
 * Represents a single delivery attempt for a webhook event.
 *
 * @property string $id
 * @property string $webhook_id
 * @property string $event_type
 * @property array<string, mixed> $payload
 * @property int $attempt
 * @property int|null $response_status
 * @property string|null $response_body
 * @property string|null $error_message
 * @property Carbon|null $delivered_at
 * @property Carbon $created_at
 * @property-read Webhook $webhook
 */
class WebhookDelivery extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * Indicates if the model should be timestamped.
     *
     * We only use created_at (no updated_at for immutable delivery logs).
     *
     * @var bool
     */
    public const UPDATED_AT = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'webhook_id',
        'event_type',
        'payload',
        'attempt',
        'response_status',
        'response_body',
        'error_message',
        'delivered_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'payload' => 'array',
        'attempt' => 'integer',
        'response_status' => 'integer',
        'delivered_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    /**
     * Get the webhook that owns this delivery.
     */
    public function webhook(): BelongsTo
    {
        return $this->belongsTo(Webhook::class);
    }

    /**
     * Check if delivery was successful.
     */
    public function wasSuccessful(): bool
    {
        return $this->response_status !== null
            && $this->response_status >= 200
            && $this->response_status < 300;
    }

    /**
     * Check if delivery failed.
     */
    public function failed(): bool
    {
        return ! $this->wasSuccessful();
    }

    /**
     * Check if this is the final retry attempt.
     */
    public function isFinalAttempt(): bool
    {
        $maxAttempts = config('rate-limiting.webhooks.max_retries', 5);

        return $this->attempt >= $maxAttempts;
    }

    /**
     * Get retry delay in seconds for next attempt.
     */
    public function getRetryDelaySeconds(): int
    {
        $retryDelays = config('rate-limiting.webhooks.retry_delay_seconds', [2, 4, 8, 16, 32]);

        // Use attempt - 1 as array index (attempt 1 = index 0)
        $index = min($this->attempt, count($retryDelays)) - 1;

        return $retryDelays[$index] ?? end($retryDelays);
    }

    /**
     * Scope to filter successful deliveries.
     */
    public function scopeSuccessful($query)
    {
        return $query->whereBetween('response_status', [200, 299]);
    }

    /**
     * Scope to filter failed deliveries.
     */
    public function scopeFailed($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('response_status')
                ->orWhere('response_status', '<', 200)
                ->orWhere('response_status', '>=', 300);
        });
    }

    /**
     * Scope to filter by event type.
     */
    public function scopeForEvent($query, string $eventType)
    {
        return $query->where('event_type', $eventType);
    }
}
