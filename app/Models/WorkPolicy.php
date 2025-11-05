<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $organization_id
 * @property float|null $max_daily_hours
 * @property float|null $min_daily_hours
 * @property int|null $required_break_duration_minutes
 * @property float|null $overtime_threshold_hours
 * @property bool $enforce_max_hours
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Organization $organization
 *
 * @method BelongsTo<Organization, $this> organization()
 */
class WorkPolicy extends Model
{
    use HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'organization_id',
        'max_daily_hours',
        'min_daily_hours',
        'required_break_duration_minutes',
        'overtime_threshold_hours',
        'enforce_max_hours',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'max_daily_hours' => 'decimal:2',
        'min_daily_hours' => 'decimal:2',
        'required_break_duration_minutes' => 'integer',
        'overtime_threshold_hours' => 'decimal:2',
        'enforce_max_hours' => 'boolean',
    ];

    /**
     * Get the organization that owns this work policy.
     *
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Check if the given hours would exceed the maximum daily hours.
     */
    public function exceedsMaxHours(float $hours): bool
    {
        if ($this->max_daily_hours === null) {
            return false;
        }

        return $hours > $this->max_daily_hours;
    }

    /**
     * Check if the given hours would qualify as overtime.
     */
    public function isOvertime(float $hours): bool
    {
        if ($this->overtime_threshold_hours === null) {
            return false;
        }

        return $hours > $this->overtime_threshold_hours;
    }

    /**
     * Check if a break is required based on hours worked.
     */
    public function requiresBreak(float $hoursWorked): bool
    {
        if ($this->required_break_duration_minutes === null) {
            return false;
        }

        // Typically require a break after 4-6 hours of work
        return $hoursWorked >= 4.0;
    }
}
