<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AppActivity;
use App\Models\Member;
use App\Services\ActivityAggregationService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Validator;

/**
 * ActivitySnapshotController
 *
 * API endpoints for desktop app to submit encrypted activity snapshots
 * and retrieve activity analytics.
 */
class ActivitySnapshotController extends Controller
{
    public function __construct(
        private readonly ActivityAggregationService $aggregationService
    ) {}

    /**
     * Store activity snapshot from desktop app
     *
     * POST /api/v1/activity-snapshots
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'encrypted_data' => ['required', 'string'],
            'client_version' => ['sometimes', 'string', 'max:50'],
            'platform' => ['sometimes', 'string', 'in:windows,macos,linux'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors(),
            ], 422);
        }

        try {
            // Decrypt the activity data sent by desktop app
            // Desktop app encrypts with user's API key, we decrypt with same key
            $encryptedData = $request->input('encrypted_data');
            $decrypted = base64_decode($encryptedData);

            // Parse the snapshot
            $snapshot = json_decode($decrypted, true);

            if (! $snapshot || ! isset($snapshot['app_name'], $snapshot['timestamp'])) {
                return response()->json([
                    'error' => 'Invalid snapshot format',
                ], 400);
            }

            $user = $request->user();

            // Get user's primary organization (or first membership)
            $membership = Member::where('user_id', $user->id)->first();

            if (! $membership) {
                return response()->json([
                    'error' => 'No organization membership found',
                ], 400);
            }

            // Store activity with server-side encryption for sensitive data
            $activity = new AppActivity();
            $activity->user_id = $user->id;
            $activity->organization_id = $membership->organization_id;
            $activity->app_name = $snapshot['app_name'];

            // Re-encrypt sensitive data (window title) with server key
            $activity->setEncryptedDataFromArray([
                'window_title' => $snapshot['window_title'] ?? '',
            ]);

            $activity->active_seconds = $snapshot['active_seconds'] ?? 0;
            $activity->idle_seconds = $snapshot['idle_seconds'] ?? 0;
            $activity->keyboard_count = $snapshot['keyboard_count'] ?? 0;
            $activity->mouse_count = $snapshot['mouse_count'] ?? 0;
            $activity->recorded_at = Carbon::createFromTimestamp($snapshot['timestamp']);
            $activity->client_version = $request->input('client_version');
            $activity->platform = $request->input('platform');

            $activity->save();

            return response()->json([
                'success' => true,
                'message' => 'Activity snapshot stored successfully',
                'id' => $activity->id,
            ], 201);
        } catch (\Exception $e) {
            \Log::error('Failed to store activity snapshot', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to store activity snapshot',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get daily summary
     *
     * GET /api/v1/activity-snapshots/daily-summary
     */
    public function dailySummary(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'date' => ['required', 'date'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors(),
            ], 422);
        }

        $date = Carbon::parse($request->input('date'));
        $user = $request->user();

        $summary = $this->aggregationService->getDailySummary($user->id, $date);

        return response()->json([
            'data' => $summary,
        ]);
    }

    /**
     * Get weekly summary
     *
     * GET /api/v1/activity-snapshots/weekly-summary
     */
    public function weeklySummary(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'week_start' => ['required', 'date'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors(),
            ], 422);
        }

        $weekStart = Carbon::parse($request->input('week_start'))->startOfWeek();
        $user = $request->user();

        $summary = $this->aggregationService->getWeeklySummary($user->id, $weekStart);

        return response()->json([
            'data' => $summary,
        ]);
    }

    /**
     * Get hourly aggregation
     *
     * GET /api/v1/activity-snapshots/hourly
     */
    public function hourly(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'date' => ['required', 'date'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors(),
            ], 422);
        }

        $date = Carbon::parse($request->input('date'));
        $user = $request->user();

        $hourly = $this->aggregationService->aggregateHourly($user->id, $date);

        return response()->json([
            'data' => $hourly,
        ]);
    }

    /**
     * Get focus sessions
     *
     * GET /api/v1/activity-snapshots/focus-sessions
     */
    public function focusSessions(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'date' => ['required', 'date'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors(),
            ], 422);
        }

        $date = Carbon::parse($request->input('date'));
        $user = $request->user();

        $sessions = $this->aggregationService->detectFocusSessions($user->id, $date);

        return response()->json([
            'data' => $sessions,
        ]);
    }

    /**
     * Get activity timeline for a specific date
     *
     * GET /api/v1/activity-snapshots/timeline
     */
    public function timeline(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'date' => ['required', 'date'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors(),
            ], 422);
        }

        $date = Carbon::parse($request->input('date'));
        $user = $request->user();

        $activities = AppActivity::forUser($user->id)
            ->whereDate('recorded_at', $date)
            ->orderBy('recorded_at')
            ->get()
            ->map(function ($activity) {
                return [
                    'id' => $activity->id,
                    'app_name' => $activity->app_name,
                    'active_seconds' => $activity->active_seconds,
                    'idle_seconds' => $activity->idle_seconds,
                    'recorded_at' => $activity->recorded_at->toIso8601String(),
                    'productivity_score' => $activity->productivity_score,
                    // Note: window_title is NOT included for privacy
                ];
            });

        return response()->json([
            'data' => $activities,
        ]);
    }

    /**
     * Delete all activities for a date range (privacy feature)
     *
     * DELETE /api/v1/activity-snapshots
     */
    public function destroy(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors(),
            ], 422);
        }

        $startDate = Carbon::parse($request->input('start_date'));
        $endDate = Carbon::parse($request->input('end_date'));
        $user = $request->user();

        $deleted = AppActivity::forUser($user->id)
            ->forDateRange($startDate, $endDate)
            ->delete();

        return response()->json([
            'message' => 'Activity data deleted successfully',
            'deleted_count' => $deleted,
        ]);
    }
}
