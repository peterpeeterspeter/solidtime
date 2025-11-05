<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AppActivity;
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
}
