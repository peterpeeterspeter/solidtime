<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class WebhookDelivery extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'webhook_id',
        'event_type',
        'payload',
        'delivery_id',
        'status',
        'http_status_code',
        'response_body',
        'error_message',
        'attempted_at',
        'completed_at',
        'duration_ms',
        'attempt_number',
        'max_attempts',
        'next_retry_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'http_status_code' => 'integer',
        'attempted_at' => 'datetime',
        'completed_at' => 'datetime',
        'duration_ms' => 'integer',
        'attempt_number' => 'integer',
        'max_attempts' => 'integer',
        'next_retry_at' => 'datetime',
    ];

    public static function generateDeliveryId(): string
    {
        return 'del_' . Str::random(24);
    }

    public function isSuccess(): bool
    {
        return $this->status === 'success';
    }

    public function canRetry(): bool
    {
        return $this->status === 'failed' 
            && $this->attempt_number < $this->max_attempts
            && $this->next_retry_at
            && $this->next_retry_at->isPast();
    }

    public function markAsSuccess(int $httpStatus, ?string $responseBody = null, int $durationMs = 0): void
    {
        $this->update([
            'status' => 'success',
            'http_status_code' => $httpStatus,
            'response_body' => $responseBody,
            'duration_ms' => $durationMs,
            'completed_at' => now(),
            'error_message' => null,
        ]);
    }

    public function markAsFailed(string $error, ?int $httpStatus = null, ?string $responseBody = null, int $durationMs = 0): void
    {
        $nextRetryAt = null;
        if ($this->attempt_number < $this->max_attempts) {
            $backoffMinutes = pow(2, $this->attempt_number) * 5;
            $nextRetryAt = now()->addMinutes($backoffMinutes);
        }

        $this->update([
            'status' => $nextRetryAt ? 'retrying' : 'failed',
            'http_status_code' => $httpStatus,
            'response_body' => $responseBody,
            'error_message' => $error,
            'duration_ms' => $durationMs,
            'completed_at' => now(),
            'next_retry_at' => $nextRetryAt,
        ]);
    }

    public function incrementAttempt(): void
    {
        $this->increment('attempt_number');
        $this->update([
            'attempted_at' => now(),
            'status' => 'pending',
        ]);
    }

    public function webhook(): BelongsTo
    {
        return $this->belongsTo(Webhook::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeRetryable($query)
    {
        return $query->where('status', 'retrying')
            ->where('next_retry_at', '<=', now())
            ->where('attempt_number', '<', 'max_attempts');
    }

    public function scopeForEvent($query, string $eventType)
    {
        return $query->where('event_type', $eventType);
    }
}
