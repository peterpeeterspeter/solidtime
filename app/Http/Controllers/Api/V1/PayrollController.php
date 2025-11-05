<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Payroll;
use App\Services\PayrollService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/**
 * PayrollController
 *
 * Manages payroll generation, approval, and reporting.
 *
 * @tags Payroll
 */
class PayrollController extends Controller
{
    public function __construct(
        private readonly PayrollService $payrollService
    ) {
    }

    /**
     * List all payrolls for an organization.
     *
     * @operationId getPayrolls
     */
    public function index(Request $request, Organization $organization): JsonResponse
    {
        $this->checkPermission($organization, 'payrolls:view');

        $validated = $request->validate([
            'status' => ['sometimes', 'string', Rule::in(Payroll::STATUSES)],
            'start_date' => ['sometimes', 'date'],
            'end_date' => ['sometimes', 'date', 'after_or_equal:start_date'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $query = Payroll::where('organization_id', $organization->id)
            ->with(['items.user', 'approver'])
            ->orderBy('period_start', 'desc');

        if (isset($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (isset($validated['start_date']) && isset($validated['end_date'])) {
            $query->whereBetween('period_start', [$validated['start_date'], $validated['end_date']]);
        }

        $perPage = $validated['per_page'] ?? 50;
        $payrolls = $query->paginate($perPage);

        return response()->json([
            'data' => $payrolls->map(function ($payroll) {
                return $this->formatPayroll($payroll);
            }),
            'meta' => [
                'current_page' => $payrolls->currentPage(),
                'from' => $payrolls->firstItem(),
                'to' => $payrolls->lastItem(),
                'per_page' => $payrolls->perPage(),
                'total' => $payrolls->total(),
            ],
            'links' => [
                'first' => $payrolls->url(1),
                'last' => $payrolls->url($payrolls->lastPage()),
                'prev' => $payrolls->previousPageUrl(),
                'next' => $payrolls->nextPageUrl(),
            ],
        ]);
    }

    /**
     * Get a specific payroll by ID.
     *
     * @operationId getPayroll
     */
    public function show(Request $request, Organization $organization, string $payrollId): JsonResponse
    {
        $this->checkPermission($organization, 'payrolls:view');

        $payroll = Payroll::where('organization_id', $organization->id)
            ->with(['items.user', 'items.member', 'approver'])
            ->findOrFail($payrollId);

        return response()->json([
            'data' => $this->formatPayrollDetailed($payroll),
        ]);
    }

    /**
     * Generate a new payroll for a period.
     *
     * @operationId generatePayroll
     */
    public function store(Request $request, Organization $organization): JsonResponse
    {
        $this->checkPermission($organization, 'payrolls:create');

        $validated = $request->validate([
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after:period_start'],
            'overtime_threshold' => ['sometimes', 'numeric', 'min:0', 'max:24'],
            'overtime_multiplier' => ['sometimes', 'numeric', 'min:1', 'max:3'],
        ]);

        $periodStart = Carbon::parse($validated['period_start']);
        $periodEnd = Carbon::parse($validated['period_end']);
        $overtimeThreshold = $validated['overtime_threshold'] ?? 8.0;
        $overtimeMultiplier = $validated['overtime_multiplier'] ?? 1.5;

        // Check if payroll already exists for this period
        $existingPayroll = Payroll::where('organization_id', $organization->id)
            ->where('period_start', $periodStart)
            ->where('period_end', $periodEnd)
            ->first();

        if ($existingPayroll !== null) {
            return response()->json([
                'error' => 'Payroll already exists for this period',
                'existing_payroll_id' => $existingPayroll->id,
            ], 409);
        }

        $payroll = $this->payrollService->generatePayroll(
            $organization->id,
            $periodStart,
            $periodEnd,
            $overtimeThreshold,
            $overtimeMultiplier
        );

        return response()->json([
            'data' => $this->formatPayrollDetailed($payroll),
            'message' => 'Payroll generated successfully',
        ], 201);
    }

    /**
     * Approve a payroll.
     *
     * @operationId approvePayroll
     */
    public function approve(Request $request, Organization $organization, string $payrollId): JsonResponse
    {
        $this->checkPermission($organization, 'payrolls:approve');

        $payroll = Payroll::where('organization_id', $organization->id)
            ->findOrFail($payrollId);

        if (! $payroll->isDraft()) {
            return response()->json([
                'error' => 'Only draft payrolls can be approved',
                'current_status' => $payroll->status,
            ], 400);
        }

        $payroll = $this->payrollService->approvePayroll($payroll, $request->user()->id);

        return response()->json([
            'data' => $this->formatPayroll($payroll),
            'message' => 'Payroll approved successfully',
        ]);
    }

    /**
     * Mark a payroll as paid.
     *
     * @operationId markPayrollAsPaid
     */
    public function markAsPaid(Request $request, Organization $organization, string $payrollId): JsonResponse
    {
        $this->checkPermission($organization, 'payrolls:update');

        $payroll = Payroll::where('organization_id', $organization->id)
            ->findOrFail($payrollId);

        if (! $payroll->isApproved()) {
            return response()->json([
                'error' => 'Only approved payrolls can be marked as paid',
                'current_status' => $payroll->status,
            ], 400);
        }

        $payroll = $this->payrollService->markAsPaid($payroll);

        return response()->json([
            'data' => $this->formatPayroll($payroll),
            'message' => 'Payroll marked as paid successfully',
        ]);
    }

    /**
     * Delete a draft payroll.
     *
     * @operationId deletePayroll
     */
    public function destroy(Request $request, Organization $organization, string $payrollId): JsonResponse
    {
        $this->checkPermission($organization, 'payrolls:delete');

        $payroll = Payroll::where('organization_id', $organization->id)
            ->findOrFail($payrollId);

        if (! $payroll->isDraft()) {
            return response()->json([
                'error' => 'Only draft payrolls can be deleted',
                'current_status' => $payroll->status,
            ], 400);
        }

        $payroll->delete();

        return response()->json([
            'message' => 'Payroll deleted successfully',
        ]);
    }

    /**
     * Get payroll summary for a period.
     *
     * @operationId getPayrollSummary
     */
    public function summary(Request $request, Organization $organization): JsonResponse
    {
        $this->checkPermission($organization, 'payrolls:view');

        $validated = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        $summary = $this->payrollService->getPayrollSummary(
            $organization->id,
            Carbon::parse($validated['start_date']),
            Carbon::parse($validated['end_date'])
        );

        return response()->json([
            'data' => $summary,
        ]);
    }

    /**
     * Format payroll for API response (basic).
     *
     * @return array<string, mixed>
     */
    protected function formatPayroll(Payroll $payroll): array
    {
        return [
            'id' => $payroll->id,
            'organization_id' => $payroll->organization_id,
            'period_start' => $payroll->period_start->toDateString(),
            'period_end' => $payroll->period_end->toDateString(),
            'period_label' => $payroll->period_label,
            'status' => $payroll->status,
            'total_regular_hours' => $payroll->total_regular_hours,
            'total_overtime_hours' => $payroll->total_overtime_hours,
            'total_hours' => $payroll->total_hours,
            'total_earnings' => $payroll->total_earnings,
            'currency' => $payroll->currency,
            'item_count' => $payroll->items->count(),
            'approved_at' => $payroll->approved_at?->toIso8601String(),
            'approved_by' => $payroll->approver?->name,
            'created_at' => $payroll->created_at->toIso8601String(),
        ];
    }

    /**
     * Format payroll for API response (detailed with items).
     *
     * @return array<string, mixed>
     */
    protected function formatPayrollDetailed(Payroll $payroll): array
    {
        return array_merge($this->formatPayroll($payroll), [
            'items' => $payroll->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'member_id' => $item->member_id,
                    'user_id' => $item->user_id,
                    'user_name' => $item->user->name,
                    'regular_hours' => $item->regular_hours,
                    'overtime_hours' => $item->overtime_hours,
                    'total_hours' => $item->total_hours,
                    'hourly_rate' => $item->hourly_rate,
                    'overtime_rate' => $item->overtime_rate,
                    'regular_earnings' => $item->regular_earnings,
                    'overtime_earnings' => $item->overtime_earnings,
                    'total_earnings' => $item->total_earnings,
                    'time_entry_count' => count($item->time_entry_ids ?? []),
                ];
            }),
        ]);
    }
}
