<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\FocusSession;
use App\Models\Member;
use App\Services\ActivityAggregationService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FocusSessionController extends Controller
{
    public function __construct(
        private ActivityAggregationService $aggregationService
    ) {
    }

    /**
     * Get all focus sessions for a date range
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : now()->startOfWeek();
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date')) : now()->endOfWeek();

        $sessions = FocusSession::forUser($request->user()->id)
            ->forDateRange($startDate, $endDate)
            ->ordered()
            ->get();

        return response()->json([
            'data' => $sessions,
        ]);
    }

    /**
     * Get focus session statistics
     */
    public function stats(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : now()->startOfWeek();
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date')) : now()->endOfWeek();

        $stats = $this->aggregationService->getFocusStats(
            $request->user()->id,
            $startDate,
            $endDate
        );

        return response()->json([
            'data' => $stats,
        ]);
    }

    /**
     * Get focus session heatmap data
     */
    public function heatmap(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : now()->startOfMonth();
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date')) : now()->endOfMonth();

        $heatmap = $this->aggregationService->getFocusHeatmap(
            $request->user()->id,
            $startDate,
            $endDate
        );

        return response()->json([
            'data' => $heatmap,
        ]);
    }

    /**
     * Get focus session streaks
     */
    public function streaks(Request $request): JsonResponse
    {
        $startDate = now()->subMonths(3); // Look back 3 months for streaks

        $streaks = $this->aggregationService->getFocusStreaks(
            $request->user()->id,
            $startDate
        );

        return response()->json([
            'data' => $streaks,
        ]);
    }

    /**
     * Detect and persist focus sessions for a specific date
     */
    public function detect(Request $request): JsonResponse
    {
        $request->validate([
            'date' => 'required|date',
        ]);

        $user = $request->user();
        $date = Carbon::parse($request->input('date'));

        // Get user's organization
        $membership = Member::where('user_id', $user->id)->first();

        if (!$membership) {
            return response()->json([
                'error' => 'User is not a member of any organization',
            ], 400);
        }

        $sessions = $this->aggregationService->detectAndPersistFocusSessions(
            $user->id,
            $membership->organization_id,
            $date
        );

        return response()->json([
            'data' => $sessions,
            'message' => 'Focus sessions detected and saved successfully',
            'count' => $sessions->count(),
        ]);
    }

    /**
     * Get a specific focus session by ID
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $session = FocusSession::forUser($request->user()->id)
            ->where('id', $id)
            ->firstOrFail();

        return response()->json([
            'data' => $session,
        ]);
    }

    /**
     * Delete a specific focus session
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $session = FocusSession::forUser($request->user()->id)
            ->where('id', $id)
            ->firstOrFail();

        $session->delete();

        return response()->json([
            'success' => true,
            'message' => 'Focus session deleted successfully',
        ]);
    }

    /**
     * Delete all focus sessions for a date range
     */
    public function destroyRange(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $startDate = Carbon::parse($request->input('start_date'));
        $endDate = Carbon::parse($request->input('end_date'));

        $deleted = FocusSession::forUser($request->user()->id)
            ->forDateRange($startDate, $endDate)
            ->delete();

        return response()->json([
            'success' => true,
            'deleted' => $deleted,
            'message' => 'Focus sessions deleted successfully',
        ]);
    }

    /**
     * Get daily focus summary
     */
    public function dailySummary(Request $request): JsonResponse
    {
        $request->validate([
            'date' => 'nullable|date',
        ]);

        $date = $request->input('date') ? Carbon::parse($request->input('date')) : now();

        $sessions = FocusSession::forUser($request->user()->id)
            ->whereDate('start_time', $date)
            ->ordered()
            ->get();

        $totalMinutes = $sessions->sum('duration_minutes');
        $avgScore = $sessions->avg('focus_score');
        $deepWorkCount = $sessions->filter->isDeepWork()->count();

        return response()->json([
            'data' => [
                'date' => $date->format('Y-m-d'),
                'sessions' => $sessions,
                'total_sessions' => $sessions->count(),
                'total_minutes' => $totalMinutes,
                'total_hours' => round($totalMinutes / 60, 1),
                'average_score' => round($avgScore, 1),
                'deep_work_sessions' => $deepWorkCount,
            ],
        ]);
    }

    /**
     * Get top productive hours
     */
    public function productiveHours(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : now()->startOfMonth();
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date')) : now()->endOfMonth();

        $sessions = FocusSession::forUser($request->user()->id)
            ->forDateRange($startDate, $endDate)
            ->get();

        $hourlyStats = $sessions->groupBy(function ($session) {
            return (int) $session->start_time->format('H');
        })->map(function ($group) {
            return [
                'session_count' => $group->count(),
                'average_score' => round($group->avg('focus_score'), 1),
                'total_minutes' => $group->sum('duration_minutes'),
            ];
        })->sortByDesc('average_score')->toArray();

        return response()->json([
            'data' => $hourlyStats,
        ]);
    }
}
