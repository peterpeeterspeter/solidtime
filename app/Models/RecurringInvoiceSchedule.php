<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class RecurringInvoiceSchedule extends Model
{
    use HasUuids, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'organization_id',
        'client_id',
        'project_id',
        'name',
        'frequency',
        'interval',
        'day_of_month',
        'day_of_week',
        'start_date',
        'end_date',
        'next_generation_date',
        'last_generated_date',
        'max_occurrences',
        'occurrences_count',
        'status',
        'from_details',
        'to_details',
        'line_items',
        'notes',
        'terms',
        'subtotal',
        'tax_rate',
        'tax_amount',
        'discount_amount',
        'total',
        'currency',
        'due_days',
        'due_date_type',
        'auto_send',
        'auto_charge',
        'include_time_entries',
        'time_entries_from_date',
        'time_entries_to_date',
        'notify_on_generation',
        'notification_emails',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'next_generation_date' => 'date',
        'last_generated_date' => 'date',
        'time_entries_from_date' => 'date',
        'time_entries_to_date' => 'date',
        'interval' => 'integer',
        'day_of_month' => 'integer',
        'day_of_week' => 'integer',
        'max_occurrences' => 'integer',
        'occurrences_count' => 'integer',
        'subtotal' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'due_days' => 'integer',
        'auto_send' => 'boolean',
        'auto_charge' => 'boolean',
        'include_time_entries' => 'boolean',
        'notify_on_generation' => 'boolean',
        'from_details' => 'array',
        'to_details' => 'array',
        'line_items' => 'array',
        'notification_emails' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Get the user that owns the schedule.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the organization that owns the schedule.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the client for this schedule.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the project for this schedule.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Check if schedule is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if schedule is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if schedule should generate invoice today.
     */
    public function shouldGenerateToday(): bool
    {
        if (!$this->isActive()) {
            return false;
        }

        if (!$this->next_generation_date) {
            return false;
        }

        return $this->next_generation_date->isToday() || $this->next_generation_date->isPast();
    }

    /**
     * Check if schedule has reached max occurrences.
     */
    public function hasReachedMaxOccurrences(): bool
    {
        if (!$this->max_occurrences) {
            return false;
        }

        return $this->occurrences_count >= $this->max_occurrences;
    }

    /**
     * Calculate next generation date based on frequency.
     */
    public function calculateNextGenerationDate(): Carbon
    {
        $current = $this->next_generation_date ?? $this->start_date;
        $next = Carbon::parse($current);

        switch ($this->frequency) {
            case 'daily':
                $next->addDays($this->interval);
                break;

            case 'weekly':
                $next->addWeeks($this->interval);
                if ($this->day_of_week !== null) {
                    $next->next($this->day_of_week);
                }
                break;

            case 'biweekly':
                $next->addWeeks(2 * $this->interval);
                break;

            case 'monthly':
                $next->addMonths($this->interval);
                if ($this->day_of_month !== null) {
                    $next->day($this->day_of_month);
                }
                break;

            case 'quarterly':
                $next->addMonths(3 * $this->interval);
                break;

            case 'biannually':
                $next->addMonths(6 * $this->interval);
                break;

            case 'annually':
                $next->addYears($this->interval);
                break;
        }

        return $next;
    }

    /**
     * Scope to get active schedules.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to get schedules due for generation.
     */
    public function scopeDueForGeneration($query)
    {
        return $query->where('status', 'active')
            ->where('next_generation_date', '<=', now());
    }

    /**
     * Scope to get schedules by frequency.
     */
    public function scopeByFrequency($query, string $frequency)
    {
        return $query->where('frequency', $frequency);
    }
}
