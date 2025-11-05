<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

/**
 * AppActivity Model
 *
 * Stores desktop application activity snapshots with encrypted sensitive data.
 * Used for automatic time tracking and productivity analytics.
 */
class AppActivity extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'organization_id',
        'app_name',
        'encrypted_data',
        'active_seconds',
        'idle_seconds',
        'keyboard_count',
        'mouse_count',
        'recorded_at',
        'client_version',
        'platform',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
        'active_seconds' => 'integer',
        'idle_seconds' => 'integer',
        'keyboard_count' => 'integer',
        'mouse_count' => 'integer',
    ];

    /**
     * Relationships
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get decrypted window title from encrypted_data
     */
    public function getWindowTitleAttribute(): ?string
    {
        try {
            $decrypted = Crypt::decryptString($this->encrypted_data);
            $data = json_decode($decrypted, true);

            return $data['window_title'] ?? null;
        } catch (\Exception $e) {
            \Log::warning('Failed to decrypt activity data', [
                'activity_id' => $this->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Get all decrypted data
     */
    public function getDecryptedDataAttribute(): array
    {
        try {
            $decrypted = Crypt::decryptString($this->encrypted_data);

            return json_decode($decrypted, true) ?? [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Set encrypted data from array
     */
    public function setEncryptedDataFromArray(array $data): void
    {
        $json = json_encode($data);
        $this->encrypted_data = Crypt::encryptString($json);
    }

    /**
     * Scope: Filter by user
     */
    public function scopeForUser($query, string $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: Filter by organization
     */
    public function scopeForOrganization($query, string $organizationId)
    {
        return $query->where('organization_id', $organizationId);
    }

    /**
     * Scope: Filter by date range
     */
    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('recorded_at', [$startDate, $endDate]);
    }

    /**
     * Scope: Filter by app name
     */
    public function scopeForApp($query, string $appName)
    {
        return $query->where('app_name', $appName);
    }

    /**
     * Scope: Only active time (not idle)
     */
    public function scopeActiveOnly($query)
    {
        return $query->where('active_seconds', '>', 0);
    }

    /**
     * Scope: Order by recorded time
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('recorded_at', 'asc');
    }

    /**
     * Get total duration in seconds (active + idle)
     */
    public function getTotalSecondsAttribute(): int
    {
        return $this->active_seconds + $this->idle_seconds;
    }

    /**
     * Check if this activity represents active work
     */
    public function isActive(): bool
    {
        return $this->active_seconds > 0 && $this->idle_seconds < 300; // Less than 5 minutes idle
    }

    /**
     * Get productivity score (0-100)
     * Based on active time vs idle time
     */
    public function getProductivityScoreAttribute(): int
    {
        $total = $this->total_seconds;

        if ($total === 0) {
            return 0;
        }

        return (int) round(($this->active_seconds / $total) * 100);
    }
}
