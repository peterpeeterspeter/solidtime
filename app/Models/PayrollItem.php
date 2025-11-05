<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * PayrollItem Model
 *
 * Represents an individual team member's earnings within a payroll period.
 *
 * @property string $id
 * @property string $payroll_id
 * @property string $member_id
 * @property string $user_id
 * @property float $regular_hours
 * @property float $overtime_hours
 * @property float $hourly_rate
 * @property float $overtime_rate
 * @property float $regular_earnings
 * @property float $overtime_earnings
 * @property float $total_earnings
 * @property array<int, string>|null $time_entry_ids
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Payroll $payroll
 * @property-read Member $member
 * @property-read User $user
 */
class PayrollItem extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'payroll_id',
        'member_id',
        'user_id',
        'regular_hours',
        'overtime_hours',
        'hourly_rate',
        'overtime_rate',
        'regular_earnings',
        'overtime_earnings',
        'total_earnings',
        'time_entry_ids',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'regular_hours' => 'decimal:2',
        'overtime_hours' => 'decimal:2',
        'hourly_rate' => 'decimal:2',
        'overtime_rate' => 'decimal:2',
        'regular_earnings' => 'decimal:2',
        'overtime_earnings' => 'decimal:2',
        'total_earnings' => 'decimal:2',
        'time_entry_ids' => 'array',
    ];

    /**
     * Get the payroll that owns this item.
     */
    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class);
    }

    /**
     * Get the member for this payroll item.
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * Get the user for this payroll item.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get total hours worked (regular + overtime).
     */
    public function getTotalHoursAttribute(): float
    {
        return $this->regular_hours + $this->overtime_hours;
    }

    /**
     * Get effective hourly rate (total earnings / total hours).
     */
    public function getEffectiveHourlyRateAttribute(): float
    {
        if ($this->total_hours == 0) {
            return 0;
        }

        return $this->total_earnings / $this->total_hours;
    }

    /**
     * Check if this item has overtime hours.
     */
    public function hasOvertime(): bool
    {
        return $this->overtime_hours > 0;
    }

    /**
     * Get percentage of total hours that are overtime.
     */
    public function getOvertimePercentageAttribute(): float
    {
        if ($this->total_hours == 0) {
            return 0;
        }

        return ($this->overtime_hours / $this->total_hours) * 100;
    }

    /**
     * Scope to filter items with overtime.
     */
    public function scopeWithOvertime($query)
    {
        return $query->where('overtime_hours', '>', 0);
    }

    /**
     * Scope to filter by member.
     */
    public function scopeForMember($query, string $memberId)
    {
        return $query->where('member_id', $memberId);
    }

    /**
     * Scope to filter by user.
     */
    public function scopeForUser($query, string $userId)
    {
        return $query->where('user_id', $userId);
    }
}
