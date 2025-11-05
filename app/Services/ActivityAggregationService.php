<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AppActivity;
use App\Models\FocusSession;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * ActivityAggregationService
 *
 * Aggregates 10-second activity snapshots into hourly summaries and insights.
 */
class ActivityAggregationService
{
    /**
     * Aggregate activities into hourly summaries
     */
    public function aggregateHourly(string $userId, Carbon $date): array
    {
        $activities = AppActivity::forUser($userId)
            ->whereDate('recorded_at', $date)
            ->orderBy('recorded_at')
            ->get();

        if ($activities->isEmpty()) {
            return [];
        }

        // Group by hour
        $byHour = $activities->groupBy(function ($activity) {
            return $activity->recorded_at->format('Y-m-d H:00');
        });

        $hourly = [];

        foreach ($byHour as $hour => $records) {
            $hourly[$hour] = $this->summarizeHour($records);
        }

        return $hourly;
    }

    /**
     * Summarize a single hour of activity
     */
    protected function summarizeHour(Collection $records): array
    {
        $totalActive = $records->sum('active_seconds');
        $totalIdle = $records->sum('idle_seconds');
        $totalSeconds = $totalActive + $totalIdle;

        // Top apps by active time
        $topApps = $records->groupBy('app_name')
            ->map(fn ($group) => $group->sum('active_seconds'))
            ->sortDesc()
            ->take(5)
            ->map(fn ($seconds, $appName) => [
                'app_name' => $appName,
                'active_seconds' => $seconds,
                'percentage' => $totalActive > 0 ? round(($seconds / $totalActive) * 100, 1) : 0,
            ])
            ->values()
            ->toArray();

        return [
            'total_active_seconds' => $totalActive,
            'total_idle_seconds' => $totalIdle,
            'total_seconds' => $totalSeconds,
            'productivity_score' => $totalSeconds > 0 ? round(($totalActive / $totalSeconds) * 100, 1) : 0,
            'top_apps' => $topApps,
            'snapshot_count' => $records->count(),
        ];
    }

    /**
     * Get daily summary
     */
    public function getDailySummary(string $userId, Carbon $date): array
    {
        $activities = AppActivity::forUser($userId)
            ->whereDate('recorded_at', $date)
            ->get();

        if ($activities->isEmpty()) {
            return [
                'date' => $date->format('Y-m-d'),
                'total_active_seconds' => 0,
                'total_idle_seconds' => 0,
                'total_seconds' => 0,
                'productivity_score' => 0,
                'top_apps' => [],
                'hourly_distribution' => [],
            ];
        }

        $totalActive = $activities->sum('active_seconds');
        $totalIdle = $activities->sum('idle_seconds');
        $totalSeconds = $totalActive + $totalIdle;

        // Top apps
        $topApps = $activities->groupBy('app_name')
            ->map(fn ($group) => $group->sum('active_seconds'))
            ->sortDesc()
            ->take(10)
            ->map(fn ($seconds, $appName) => [
                'app_name' => $appName,
                'active_seconds' => $seconds,
                'active_minutes' => round($seconds / 60, 1),
                'percentage' => $totalActive > 0 ? round(($seconds / $totalActive) * 100, 1) : 0,
            ])
            ->values()
            ->toArray();

        // Hourly distribution
        $hourlyDistribution = $activities->groupBy(function ($activity) {
            return (int) $activity->recorded_at->format('H');
        })->map(function ($group) {
            return [
                'active_seconds' => $group->sum('active_seconds'),
                'idle_seconds' => $group->sum('idle_seconds'),
            ];
        })->toArray();

        return [
            'date' => $date->format('Y-m-d'),
            'total_active_seconds' => $totalActive,
            'total_idle_seconds' => $totalIdle,
            'total_seconds' => $totalSeconds,
            'active_hours' => round($totalActive / 3600, 2),
            'productivity_score' => $totalSeconds > 0 ? round(($totalActive / $totalSeconds) * 100, 1) : 0,
            'top_apps' => $topApps,
            'hourly_distribution' => $hourlyDistribution,
            'snapshot_count' => $activities->count(),
        ];
    }

    /**
     * Get weekly summary
     */
    public function getWeeklySummary(string $userId, Carbon $startOfWeek): array
    {
        $endOfWeek = (clone $startOfWeek)->endOfWeek();

        $activities = AppActivity::forUser($userId)
            ->forDateRange($startOfWeek, $endOfWeek)
            ->get();

        if ($activities->isEmpty()) {
            return [
                'week_start' => $startOfWeek->format('Y-m-d'),
                'week_end' => $endOfWeek->format('Y-m-d'),
                'total_active_seconds' => 0,
                'total_active_hours' => 0,
                'average_daily_hours' => 0,
                'top_apps' => [],
                'daily_breakdown' => [],
            ];
        }

        $totalActive = $activities->sum('active_seconds');

        // Top apps
        $topApps = $activities->groupBy('app_name')
            ->map(fn ($group) => $group->sum('active_seconds'))
            ->sortDesc()
            ->take(10)
            ->map(fn ($seconds, $appName) => [
                'app_name' => $appName,
                'active_seconds' => $seconds,
                'active_hours' => round($seconds / 3600, 2),
                'percentage' => $totalActive > 0 ? round(($seconds / $totalActive) * 100, 1) : 0,
            ])
            ->values()
            ->toArray();

        // Daily breakdown
        $dailyBreakdown = $activities->groupBy(function ($activity) {
            return $activity->recorded_at->format('Y-m-d');
        })->map(function ($group, $date) {
            $active = $group->sum('active_seconds');

            return [
                'date' => $date,
                'active_seconds' => $active,
                'active_hours' => round($active / 3600, 2),
            ];
        })->values()->toArray();

        return [
            'week_start' => $startOfWeek->format('Y-m-d'),
            'week_end' => $endOfWeek->format('Y-m-d'),
            'total_active_seconds' => $totalActive,
            'total_active_hours' => round($totalActive / 3600, 2),
            'average_daily_hours' => round(($totalActive / 3600) / 7, 2),
            'top_apps' => $topApps,
            'daily_breakdown' => $dailyBreakdown,
        ];
    }

    /**
     * Detect focus sessions
     *
     * Focus session = 20+ minutes continuous work, <3 app switches, no idle >5 minutes
     */
    public function detectFocusSessions(string $userId, Carbon $date): array
    {
        $activities = AppActivity::forUser($userId)
            ->whereDate('recorded_at', $date)
            ->orderBy('recorded_at')
            ->get();

        if ($activities->isEmpty()) {
            return [];
        }

        $sessions = [];
        $currentSession = null;

        foreach ($activities as $activity) {
            // Start new session if active and no current session
            if ($currentSession === null && $activity->active_seconds > 0) {
                $currentSession = [
                    'start' => $activity->recorded_at,
                    'apps' => [$activity->app_name],
                    'app_switches' => 0,
                    'active_seconds' => $activity->active_seconds,
                    'snapshots' => 1,
                ];
                continue;
            }

            // Continue or end current session
            if ($currentSession !== null) {
                // End session if idle for >5 minutes
                if ($activity->idle_seconds > 300) {
                    $currentSession['end'] = $activity->recorded_at;
                    $duration = $currentSession['end']->diffInSeconds($currentSession['start']);

                    // Only keep sessions >=20 minutes
                    if ($duration >= 1200) {
                        $currentSession['duration_seconds'] = $duration;
                        $currentSession['duration_minutes'] = round($duration / 60, 1);
                        $currentSession['unique_apps'] = count(array_unique($currentSession['apps']));
                        $currentSession['focus_score'] = $this->calculateFocusScore($currentSession);
                        $sessions[] = $currentSession;
                    }

                    $currentSession = null;
                    continue;
                }

                // Continue session
                $currentSession['active_seconds'] += $activity->active_seconds;
                $currentSession['snapshots']++;

                // Track app switches
                $lastApp = end($currentSession['apps']);
                if ($activity->app_name !== $lastApp) {
                    $currentSession['app_switches']++;
                }

                $currentSession['apps'][] = $activity->app_name;
            }
        }

        // Close final session if it exists
        if ($currentSession !== null) {
            $currentSession['end'] = $activities->last()->recorded_at;
            $duration = $currentSession['end']->diffInSeconds($currentSession['start']);

            if ($duration >= 1200) {
                $currentSession['duration_seconds'] = $duration;
                $currentSession['duration_minutes'] = round($duration / 60, 1);
                $currentSession['unique_apps'] = count(array_unique($currentSession['apps']));
                $currentSession['focus_score'] = $this->calculateFocusScore($currentSession);
                $sessions[] = $currentSession;
            }
        }

        return $sessions;
    }

    /**
     * Calculate focus score for a session (0-100)
     *
     * Factors:
     * - Duration (longer = better)
     * - App switches (fewer = better)
     * - Unique apps (fewer = better)
     */
    protected function calculateFocusScore(array $session): int
    {
        $durationScore = min(100, ($session['duration_seconds'] / 3600) * 50); // Max at 2 hours
        $switchScore = max(0, 100 - ($session['app_switches'] * 5)); // Penalty per switch
        $appScore = max(0, 100 - ($session['unique_apps'] * 10)); // Penalty per unique app

        $score = ($durationScore * 0.5) + ($switchScore * 0.3) + ($appScore * 0.2);

        return (int) round($score);
    }

    /**
     * Detect and persist focus sessions for a user on a specific date
     */
    public function detectAndPersistFocusSessions(string $userId, string $organizationId, Carbon $date): Collection
    {
        // Delete existing focus sessions for this date to avoid duplicates
        FocusSession::forUser($userId)
            ->whereDate('start_time', $date)
            ->delete();

        // Detect sessions
        $detectedSessions = $this->detectFocusSessions($userId, $date);

        $persistedSessions = collect();

        foreach ($detectedSessions as $session) {
            $uniqueApps = array_unique($session['apps']);
            $appCounts = array_count_values($session['apps']);
            arsort($appCounts);
            $primaryApp = array_key_first($appCounts);

            $focusSession = FocusSession::create([
                'user_id' => $userId,
                'organization_id' => $organizationId,
                'start_time' => $session['start'],
                'end_time' => $session['end'],
                'duration_minutes' => (int) $session['duration_minutes'],
                'app_switches' => $session['app_switches'],
                'unique_apps_count' => count($uniqueApps),
                'interruptions_count' => $session['app_switches'], // Same as app switches for now
                'focus_score' => $session['focus_score'],
                'apps_used' => $uniqueApps,
                'primary_app' => $primaryApp,
            ]);

            $persistedSessions->push($focusSession);
        }

        return $persistedSessions;
    }

    /**
     * Get focus session statistics for a date range
     */
    public function getFocusStats(string $userId, Carbon $startDate, Carbon $endDate): array
    {
        $sessions = FocusSession::forUser($userId)
            ->forDateRange($startDate, $endDate)
            ->get();

        if ($sessions->isEmpty()) {
            return [
                'total_sessions' => 0,
                'total_focus_minutes' => 0,
                'average_duration_minutes' => 0,
                'average_focus_score' => 0,
                'deep_work_sessions' => 0,
                'top_focus_apps' => [],
            ];
        }

        $totalMinutes = $sessions->sum('duration_minutes');
        $deepWorkCount = $sessions->filter->isDeepWork()->count();

        // Top focus apps (based on primary app in sessions)
        $appCounts = $sessions->groupBy('primary_app')
            ->map->count()
            ->sortDesc()
            ->take(5)
            ->map(fn ($count, $app) => [
                'app_name' => $app,
                'session_count' => $count,
            ])
            ->values()
            ->toArray();

        return [
            'total_sessions' => $sessions->count(),
            'total_focus_minutes' => $totalMinutes,
            'total_focus_hours' => round($totalMinutes / 60, 1),
            'average_duration_minutes' => round($totalMinutes / $sessions->count(), 1),
            'average_focus_score' => round($sessions->avg('focus_score'), 1),
            'deep_work_sessions' => $deepWorkCount,
            'deep_work_percentage' => round(($deepWorkCount / $sessions->count()) * 100, 1),
            'top_focus_apps' => $appCounts,
        ];
    }

    /**
     * Get focus session heatmap data for visualization
     *
     * Returns array of [date => hour => focus_score]
     */
    public function getFocusHeatmap(string $userId, Carbon $startDate, Carbon $endDate): array
    {
        $sessions = FocusSession::forUser($userId)
            ->forDateRange($startDate, $endDate)
            ->get();

        $heatmap = [];

        foreach ($sessions as $session) {
            $date = $session->start_time->format('Y-m-d');
            $hour = (int) $session->start_time->format('H');

            if (!isset($heatmap[$date])) {
                $heatmap[$date] = array_fill(0, 24, null);
            }

            // Take the highest focus score if multiple sessions in same hour
            if ($heatmap[$date][$hour] === null || $session->focus_score > $heatmap[$date][$hour]) {
                $heatmap[$date][$hour] = $session->focus_score;
            }
        }

        return $heatmap;
    }

    /**
     * Get focus session streaks
     */
    public function getFocusStreaks(string $userId, Carbon $startDate): array
    {
        $sessions = FocusSession::forUser($userId)
            ->where('start_time', '>=', $startDate)
            ->minimumDuration(20)
            ->ordered()
            ->get();

        $currentStreak = 0;
        $longestStreak = 0;
        $lastDate = null;

        foreach ($sessions as $session) {
            $sessionDate = $session->start_time->format('Y-m-d');

            if ($lastDate === null || $session->start_time->diffInDays($lastDate) <= 1) {
                if ($lastDate === null || $sessionDate !== $lastDate->format('Y-m-d')) {
                    $currentStreak++;
                }
            } else {
                // Streak broken
                $longestStreak = max($longestStreak, $currentStreak);
                $currentStreak = 1;
            }

            $lastDate = $session->start_time;
        }

        $longestStreak = max($longestStreak, $currentStreak);

        return [
            'current_streak' => $currentStreak,
            'longest_streak' => $longestStreak,
        ];
    }
}
