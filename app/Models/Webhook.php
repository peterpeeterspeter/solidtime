<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Webhook Model
 *
 * Represents a webhook subscription that receives real-time event notifications.
 *
 * @property string $id
 * @property string $user_id
 * @property string $url
 * @property string $secret
 * @property array<int, string> $events
 * @property bool $is_active
 * @property Carbon|null $last_delivery_at
 * @property int $delivery_success_count
 * @property int $delivery_failure_count
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read User $user
 * @property-read \Illuminate\Database\Eloquent\Collection<int, WebhookDelivery> $deliveries
 */
class Webhook extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'url',
        'secret',
        'events',
        'is_active',
        'last_delivery_at',
        'delivery_success_count',
        'delivery_failure_count',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'events' => 'array',
        'is_active' => 'boolean',
        'last_delivery_at' => 'datetime',
        'delivery_success_count' => 'integer',
        'delivery_failure_count' => 'integer',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'secret', // Never expose webhook secret in API responses
    ];

    /**
     * Available webhook events
     *
     * @var array<int, string>
     */
    public const AVAILABLE_EVENTS = [
        // Time Tracking Events
        'time_entry.created',
        'time_entry.updated',
        'time_entry.deleted',

        // Project Events
        'project.created',
        'project.updated',
        'project.archived',

        // Invoice Events
        'invoice.created',
        'invoice.sent',
        'invoice.paid',
        'invoice.overdue',

        // Payment Events
        'payment.received',
        'payment.refunded',
        'payment.failed',

        // Team Events
        'member.added',
        'member.removed',
        'member.role_changed',
    ];

    /**
     * Get the user that owns the webhook.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all deliveries for this webhook.
     */
    public function deliveries(): HasMany
    {
        return $this->hasMany(WebhookDelivery::class);
    }

    /**
     * Get recent deliveries (last 100).
     */
    public function recentDeliveries(): HasMany
    {
        return $this->deliveries()
            ->orderBy('created_at', 'desc')
            ->limit(100);
    }

    /**
     * Check if this webhook is subscribed to a specific event.
     */
    public function isSubscribedTo(string $event): bool
    {
        return in_array($event, $this->events, true);
    }

    /**
     * Calculate delivery success rate.
     */
    public function getSuccessRateAttribute(): float
    {
        $total = $this->delivery_success_count + $this->delivery_failure_count;

        if ($total === 0) {
            return 0.0;
        }

        return round(($this->delivery_success_count / $total) * 100, 2);
    }

    /**
     * Increment success counter.
     */
    public function incrementSuccessCount(): void
    {
        $this->increment('delivery_success_count');
        $this->update(['last_delivery_at' => now()]);
    }

    /**
     * Increment failure counter.
     */
    public function incrementFailureCount(): void
    {
        $this->increment('delivery_failure_count');
        $this->update(['last_delivery_at' => now()]);
    }

    /**
     * Disable webhook after too many failures.
     */
    public function disableIfTooManyFailures(int $threshold = 50): void
    {
        if ($this->delivery_failure_count >= $threshold) {
            $this->update(['is_active' => false]);
        }
    }
}
