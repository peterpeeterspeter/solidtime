<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FocusSession extends Model
{
    use HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'organization_id',
        'start_time',
        'end_time',
        'duration_minutes',
        'app_switches',
        'unique_apps_count',
        'interruptions_count',
        'focus_score',
        'apps_used',
        'primary_app',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'duration_minutes' => 'integer',
        'app_switches' => 'integer',
        'unique_apps_count' => 'integer',
        'interruptions_count' => 'integer',
        'focus_score' => 'integer',
        'apps_used' => 'array',
    ];

    /**
     * Get the user that owns the focus session.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the organization that owns the focus session.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Scope a query to only include sessions for a specific user.
     */
    public function scopeForUser($query, string $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to only include sessions for a specific organization.
     */
    public function scopeForOrganization($query, string $organizationId)
    {
        return $query->where('organization_id', $organizationId);
    }

    /**
     * Scope a query to only include sessions within a date range.
     */
    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('start_time', [$startDate, $endDate]);
    }

    /**
     * Scope a query to only include sessions with a minimum duration.
     */
    public function scopeMinimumDuration($query, int $minutes)
    {
        return $query->where('duration_minutes', '>=', $minutes);
    }

    /**
     * Scope a query to only include sessions with a minimum focus score.
     */
    public function scopeMinimumScore($query, int $score)
    {
        return $query->where('focus_score', '>=', $score);
    }

    /**
     * Scope a query to order by focus score descending.
     */
    public function scopeOrderedByScore($query)
    {
        return $query->orderBy('focus_score', 'desc');
    }

    /**
     * Scope a query to order by start time ascending.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('start_time', 'asc');
    }

    /**
     * Get the focus quality category.
     */
    public function getFocusQualityAttribute(): string
    {
        return match (true) {
            $this->focus_score >= 80 => 'excellent',
            $this->focus_score >= 60 => 'good',
            $this->focus_score >= 40 => 'fair',
            default => 'poor',
        };
    }

    /**
     * Check if this session qualifies as a deep work session.
     */
    public function isDeepWork(): bool
    {
        return $this->duration_minutes >= 40
            && $this->focus_score >= 70
            && $this->app_switches < 3;
    }

    /**
     * Get formatted duration string.
     */
    public function getFormattedDurationAttribute(): string
    {
        $hours = floor($this->duration_minutes / 60);
        $minutes = $this->duration_minutes % 60;

        if ($hours > 0) {
            return "{$hours}h {$minutes}m";
        }

        return "{$minutes}m";
    }
}
