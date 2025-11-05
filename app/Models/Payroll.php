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
 * Payroll Model
 *
 * Represents a payroll period for an organization with calculated earnings.
 *
 * @property string $id
 * @property string $organization_id
 * @property Carbon $period_start
 * @property Carbon $period_end
 * @property string $status
 * @property float $total_regular_hours
 * @property float $total_overtime_hours
 * @property float $total_earnings
 * @property string $currency
 * @property Carbon|null $approved_at
 * @property string|null $approved_by
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Organization $organization
 * @property-read User|null $approver
 * @property-read \Illuminate\Database\Eloquent\Collection<int, PayrollItem> $items
 */
class Payroll extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * Payroll status constants
     */
    public const STATUS_DRAFT = 'draft';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_PAID = 'paid';

    /**
     * Available statuses
     *
     * @var array<int, string>
     */
    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_APPROVED,
        self::STATUS_PAID,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'organization_id',
        'period_start',
        'period_end',
        'status',
        'total_regular_hours',
        'total_overtime_hours',
        'total_earnings',
        'currency',
        'approved_at',
        'approved_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'total_regular_hours' => 'decimal:2',
        'total_overtime_hours' => 'decimal:2',
        'total_earnings' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    /**
     * Get the organization that owns this payroll.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the user who approved this payroll.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get all payroll items for this payroll.
     */
    public function items(): HasMany
    {
        return $this->hasMany(PayrollItem::class);
    }

    /**
     * Check if payroll is in draft status.
     */
    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    /**
     * Check if payroll is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Check if payroll is paid.
     */
    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    /**
     * Approve the payroll.
     */
    public function approve(string $userId): void
    {
        $this->update([
            'status' => self::STATUS_APPROVED,
            'approved_at' => now(),
            'approved_by' => $userId,
        ]);
    }

    /**
     * Mark payroll as paid.
     */
    public function markAsPaid(): void
    {
        $this->update([
            'status' => self::STATUS_PAID,
        ]);
    }

    /**
     * Get period label (e.g., "Nov 1-15, 2025").
     */
    public function getPeriodLabelAttribute(): string
    {
        return $this->period_start->format('M j').' - '.$this->period_end->format('M j, Y');
    }

    /**
     * Get total hours (regular + overtime).
     */
    public function getTotalHoursAttribute(): float
    {
        return $this->total_regular_hours + $this->total_overtime_hours;
    }

    /**
     * Calculate average hourly rate across all items.
     */
    public function getAverageHourlyRateAttribute(): float
    {
        if ($this->total_hours == 0) {
            return 0;
        }

        return $this->total_earnings / $this->total_hours;
    }

    /**
     * Scope to filter by status.
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter by period.
     */
    public function scopeForPeriod($query, Carbon $start, Carbon $end)
    {
        return $query->where('period_start', '>=', $start)
            ->where('period_end', '<=', $end);
    }

    /**
     * Scope to filter by organization.
     */
    public function scopeForOrganization($query, string $organizationId)
    {
        return $query->where('organization_id', $organizationId);
    }
}
