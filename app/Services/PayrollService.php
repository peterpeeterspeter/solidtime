<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Member;
use App\Models\Organization;
use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Models\TimeEntry;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * PayrollService
 *
 * Handles payroll generation, calculation, and approval logic.
 */
class PayrollService
{
    public function __construct(
        private readonly WebhookService $webhookService
    ) {
    }

    /**
     * Generate payroll for an organization for a specific period.
     *
     * @param  string  $organizationId  Organization ID
     * @param  Carbon  $periodStart  Start date of payroll period
     * @param  Carbon  $periodEnd  End date of payroll period
     * @param  float  $overtimeThreshold  Daily hours threshold for overtime (default: 8.0)
     * @param  float  $overtimeMultiplier  Overtime rate multiplier (default: 1.5)
     * @return Payroll Generated payroll with items
     */
    public function generatePayroll(
        string $organizationId,
        Carbon $periodStart,
        Carbon $periodEnd,
        float $overtimeThreshold = 8.0,
        float $overtimeMultiplier = 1.5
    ): Payroll {
        $organization = Organization::findOrFail($organizationId);

        // Create payroll record
        $payroll = Payroll::create([
            'organization_id' => $organizationId,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'status' => Payroll::STATUS_DRAFT,
            'currency' => $organization->currency ?? 'USD',
        ]);

        // Get all members with billable rates
        $members = Member::where('organization_id', $organizationId)
            ->whereNotNull('billable_rate')
            ->where('billable_rate', '>', 0)
            ->get();

        $totalRegularHours = 0;
        $totalOvertimeHours = 0;
        $totalEarnings = 0;

        foreach ($members as $member) {
            $payrollItem = $this->calculatePayrollForMember(
                $payroll,
                $member,
                $periodStart,
                $periodEnd,
                $overtimeThreshold,
                $overtimeMultiplier
            );

            if ($payrollItem !== null) {
                $totalRegularHours += $payrollItem->regular_hours;
                $totalOvertimeHours += $payrollItem->overtime_hours;
                $totalEarnings += $payrollItem->total_earnings;
            }
        }

        // Update payroll totals
        $payroll->update([
            'total_regular_hours' => $totalRegularHours,
            'total_overtime_hours' => $totalOvertimeHours,
            'total_earnings' => $totalEarnings,
        ]);

        // Dispatch webhook event
        $this->webhookService->dispatch('payroll.generated', [
            'id' => $payroll->id,
            'organization_id' => $payroll->organization_id,
            'period_start' => $payroll->period_start->toDateString(),
            'period_end' => $payroll->period_end->toDateString(),
            'total_earnings' => $payroll->total_earnings,
            'currency' => $payroll->currency,
            'status' => $payroll->status,
        ]);

        return $payroll->fresh(['items.member.user', 'items.user']);
    }

    /**
     * Calculate payroll for a single member.
     */
    protected function calculatePayrollForMember(
        Payroll $payroll,
        Member $member,
        Carbon $periodStart,
        Carbon $periodEnd,
        float $overtimeThreshold,
        float $overtimeMultiplier
    ): ?PayrollItem {
        // Get all time entries for this member in the period
        $timeEntries = TimeEntry::where('user_id', $member->user_id)
            ->where('organization_id', $payroll->organization_id)
            ->whereBetween('start', [$periodStart->startOfDay(), $periodEnd->endOfDay()])
            ->whereNotNull('end')
            ->orderBy('start')
            ->get();

        if ($timeEntries->isEmpty()) {
            return null;
        }

        // Group time entries by day to calculate overtime
        $entriesByDay = $timeEntries->groupBy(function ($entry) {
            return $entry->start->format('Y-m-d');
        });

        $regularHours = 0;
        $overtimeHours = 0;
        $timeEntryIds = [];

        foreach ($entriesByDay as $date => $dayEntries) {
            $dayHours = $this->calculateDailyHours($dayEntries);
            $timeEntryIds = array_merge($timeEntryIds, $dayEntries->pluck('id')->toArray());

            if ($dayHours > $overtimeThreshold) {
                $regularHours += $overtimeThreshold;
                $overtimeHours += ($dayHours - $overtimeThreshold);
            } else {
                $regularHours += $dayHours;
            }
        }

        $hourlyRate = $member->billable_rate;
        $overtimeRate = $hourlyRate * $overtimeMultiplier;
        $regularEarnings = $regularHours * $hourlyRate;
        $overtimeEarnings = $overtimeHours * $overtimeRate;
        $totalEarnings = $regularEarnings + $overtimeEarnings;

        return PayrollItem::create([
            'payroll_id' => $payroll->id,
            'member_id' => $member->id,
            'user_id' => $member->user_id,
            'regular_hours' => round($regularHours, 2),
            'overtime_hours' => round($overtimeHours, 2),
            'hourly_rate' => $hourlyRate,
            'overtime_rate' => $overtimeRate,
            'regular_earnings' => round($regularEarnings, 2),
            'overtime_earnings' => round($overtimeEarnings, 2),
            'total_earnings' => round($totalEarnings, 2),
            'time_entry_ids' => $timeEntryIds,
        ]);
    }

    /**
     * Calculate total hours for a day's time entries.
     *
     * @param  Collection<int, TimeEntry>  $dayEntries
     */
    protected function calculateDailyHours(Collection $dayEntries): float
    {
        $totalSeconds = 0;

        foreach ($dayEntries as $entry) {
            if ($entry->end !== null) {
                $totalSeconds += $entry->start->diffInSeconds($entry->end);
            }
        }

        return $totalSeconds / 3600; // Convert to hours
    }

    /**
     * Approve a payroll.
     *
     * @param  Payroll  $payroll  Payroll to approve
     * @param  string  $userId  User ID approving the payroll
     * @return Payroll Approved payroll
     */
    public function approvePayroll(Payroll $payroll, string $userId): Payroll
    {
        if (! $payroll->isDraft()) {
            throw new \RuntimeException('Only draft payrolls can be approved');
        }

        $payroll->approve($userId);

        // Dispatch webhook event
        $this->webhookService->dispatch('payroll.approved', [
            'id' => $payroll->id,
            'organization_id' => $payroll->organization_id,
            'period_start' => $payroll->period_start->toDateString(),
            'period_end' => $payroll->period_end->toDateString(),
            'total_earnings' => $payroll->total_earnings,
            'currency' => $payroll->currency,
            'approved_at' => $payroll->approved_at->toIso8601String(),
            'approved_by' => $payroll->approved_by,
        ]);

        return $payroll->fresh();
    }

    /**
     * Mark a payroll as paid.
     *
     * @param  Payroll  $payroll  Payroll to mark as paid
     * @return Payroll Updated payroll
     */
    public function markAsPaid(Payroll $payroll): Payroll
    {
        if (! $payroll->isApproved()) {
            throw new \RuntimeException('Only approved payrolls can be marked as paid');
        }

        $payroll->markAsPaid();

        // Dispatch webhook event
        $this->webhookService->dispatch('payroll.paid', [
            'id' => $payroll->id,
            'organization_id' => $payroll->organization_id,
            'period_start' => $payroll->period_start->toDateString(),
            'period_end' => $payroll->period_end->toDateString(),
            'total_earnings' => $payroll->total_earnings,
            'currency' => $payroll->currency,
        ]);

        return $payroll->fresh();
    }

    /**
     * Get payroll summary for an organization.
     *
     * @return array<string, mixed>
     */
    public function getPayrollSummary(string $organizationId, Carbon $periodStart, Carbon $periodEnd): array
    {
        $payrolls = Payroll::where('organization_id', $organizationId)
            ->whereBetween('period_start', [$periodStart, $periodEnd])
            ->get();

        return [
            'total_payrolls' => $payrolls->count(),
            'draft_count' => $payrolls->where('status', Payroll::STATUS_DRAFT)->count(),
            'approved_count' => $payrolls->where('status', Payroll::STATUS_APPROVED)->count(),
            'paid_count' => $payrolls->where('status', Payroll::STATUS_PAID)->count(),
            'total_earnings' => $payrolls->sum('total_earnings'),
            'total_hours' => $payrolls->sum(fn ($p) => $p->total_hours),
        ];
    }
}
