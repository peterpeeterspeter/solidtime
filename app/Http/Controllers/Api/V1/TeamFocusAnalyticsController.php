<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Services\TeamFocusAnalyticsService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TeamFocusAnalyticsController extends Controller
{
    public function __construct(
        private TeamFocusAnalyticsService $teamAnalyticsService
    ) {
    }

    /**
     * Get team-level focus statistics
     */
    public function teamStats(Request $request, string $organizationId): JsonResponse
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        // Verify user is a member of this organization
        $membership = Member::where('user_id', $request->user()->id)
            ->where('organization_id', $organizationId)
            ->first();

        if (!$membership) {
            return response()->json([
                'error' => 'You are not a member of this organization',
            ], 403);
        }

        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : now()->startOfWeek();
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date')) : now()->endOfWeek();

        $stats = $this->teamAnalyticsService->getTeamStats($organizationId, $startDate, $endDate);

        return response()->json([
            'data' => $stats,
        ]);
    }

    /**
     * Get member rankings
     */
    public function memberRankings(Request $request, string $organizationId): JsonResponse
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        // Verify user is a member of this organization
        $membership = Member::where('user_id', $request->user()->id)
            ->where('organization_id', $organizationId)
            ->first();

        if (!$membership) {
            return response()->json([
                'error' => 'You are not a member of this organization',
            ], 403);
        }

        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : now()->startOfWeek();
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date')) : now()->endOfWeek();

        $rankings = $this->teamAnalyticsService->getMemberRankings($organizationId, $startDate, $endDate);

        return response()->json([
            'data' => $rankings,
        ]);
    }

    /**
     * Get team heatmap
     */
    public function teamHeatmap(Request $request, string $organizationId): JsonResponse
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        // Verify user is a member of this organization
        $membership = Member::where('user_id', $request->user()->id)
            ->where('organization_id', $organizationId)
            ->first();

        if (!$membership) {
            return response()->json([
                'error' => 'You are not a member of this organization',
            ], 403);
        }

        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : now()->startOfMonth();
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date')) : now()->endOfMonth();

        $heatmap = $this->teamAnalyticsService->getTeamHeatmap($organizationId, $startDate, $endDate);

        return response()->json([
            'data' => $heatmap,
        ]);
    }

    /**
     * Get team productive hours
     */
    public function teamProductiveHours(Request $request, string $organizationId): JsonResponse
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        // Verify user is a member of this organization
        $membership = Member::where('user_id', $request->user()->id)
            ->where('organization_id', $organizationId)
            ->first();

        if (!$membership) {
            return response()->json([
                'error' => 'You are not a member of this organization',
            ], 403);
        }

        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : now()->startOfMonth();
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date')) : now()->endOfMonth();

        $productiveHours = $this->teamAnalyticsService->getTeamProductiveHours($organizationId, $startDate, $endDate);

        return response()->json([
            'data' => $productiveHours,
        ]);
    }

    /**
     * Get team focus distribution by time of day
     */
    public function teamFocusDistribution(Request $request, string $organizationId): JsonResponse
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        // Verify user is a member of this organization
        $membership = Member::where('user_id', $request->user()->id)
            ->where('organization_id', $organizationId)
            ->first();

        if (!$membership) {
            return response()->json([
                'error' => 'You are not a member of this organization',
            ], 403);
        }

        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : now()->startOfWeek();
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date')) : now()->endOfWeek();

        $distribution = $this->teamAnalyticsService->getTeamFocusDistribution($organizationId, $startDate, $endDate);

        return response()->json([
            'data' => $distribution,
        ]);
    }

    /**
     * Get team insights and recommendations
     */
    public function teamInsights(Request $request, string $organizationId): JsonResponse
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        // Verify user is a member of this organization
        $membership = Member::where('user_id', $request->user()->id)
            ->where('organization_id', $organizationId)
            ->first();

        if (!$membership) {
            return response()->json([
                'error' => 'You are not a member of this organization',
            ], 403);
        }

        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : now()->startOfWeek();
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date')) : now()->endOfWeek();

        $insights = $this->teamAnalyticsService->getTeamInsights($organizationId, $startDate, $endDate);

        return response()->json([
            'data' => $insights,
        ]);
    }
}
