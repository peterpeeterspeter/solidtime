<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\WebhookFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class Webhook extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'organization_id',
        'name',
        'description',
        'url',
        'secret',
        'events',
        'filters',
        'is_active',
        'failure_count',
        'last_triggered_at',
        'last_success_at',
        'last_failure_at',
        'last_error',
        'verification_status',
        'verified_at',
    ];

    protected $casts = [
        'events' => 'array',
        'filters' => 'array',
        'is_active' => 'boolean',
        'failure_count' => 'integer',
        'last_triggered_at' => 'datetime',
        'last_success_at' => 'datetime',
        'last_failure_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    protected $hidden = [
        'secret',
    ];

    /** Available webhook event types */
    public const EVENTS = [
        // Time entries
        'time_entry.started' => 'Time entry started',
        'time_entry.stopped' => 'Time entry stopped',
        'time_entry.created' => 'Time entry created',
        'time_entry.updated' => 'Time entry updated',
        'time_entry.deleted' => 'Time entry deleted',

        // Focus sessions
        'focus_session.detected' => 'Focus session detected',
        'focus_session.completed' => 'Focus session completed',

        // Projects
        'project.created' => 'Project created',
        'project.updated' => 'Project updated',
        'project.deleted' => 'Project deleted',

        // Tasks
        'task.created' => 'Task created',
        'task.updated' => 'Task updated',
        'task.deleted' => 'Task deleted',
        'task.completed' => 'Task completed',

        // Team
        'member.added' => 'Team member added',
        'member.removed' => 'Team member removed',

        // Reports
        'report.generated' => 'Report generated',
        'timesheet.exported' => 'Timesheet exported',

        // Invoices
        'invoice.created' => 'Invoice created',
        'invoice.sent' => 'Invoice sent',
        'invoice.paid' => 'Invoice marked as paid',
    ];

    public static function generateSecret(): string
    {
        return 'whsec_' . Str::random(48);
    }

    public function isSubscribedTo(string $eventType): bool
    {
        return in_array($eventType, $this->events, true) || in_array('*', $this->events, true);
    }

    public function isHealthy(): bool
    {
        return $this->is_active && $this->failure_count < 10;
    }

    public function recordSuccess(): void
    {
        $this->update([
            'last_triggered_at' => now(),
            'last_success_at' => now(),
            'failure_count' => 0,
            'last_error' => null,
        ]);
    }

    public function recordFailure(string $error): void
    {
        $this->increment('failure_count');
        $this->update([
            'last_triggered_at' => now(),
            'last_failure_at' => now(),
            'last_error' => $error,
        ]);

        if ($this->failure_count >= 10) {
            $this->update(['is_active' => false]);
        }
    }

    public function markVerified(): void
    {
        $this->update([
            'verification_status' => 'verified',
            'verified_at' => now(),
        ]);
    }

    public function disable(): void
    {
        $this->update(['is_active' => false]);
    }

    public function enable(): void
    {
        $this->update(['is_active' => true, 'failure_count' => 0]);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(WebhookDelivery::class);
    }

    public function recentDeliveries()
    {
        return $this->deliveries()->latest()->limit(100);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeHealthy($query)
    {
        return $query->where('is_active', true)->where('failure_count', '<', 10);
    }

    public function scopeForOrganization($query, string $organizationId)
    {
        return $query->where('organization_id', $organizationId);
    }

    public function scopeSubscribedTo($query, string $eventType)
    {
        return $query->where(function ($q) use ($eventType) {
            $q->whereJsonContains('events', $eventType)
                ->orWhereJsonContains('events', '*');
        });
    }
}
