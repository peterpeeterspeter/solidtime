<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\FocusSession;
use App\Models\Member;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * TeamFocusAnalyticsService
 *
 * Provides organization-level focus session analytics (anonymized)
 */
class TeamFocusAnalyticsService
{
    /**
     * Get team-level focus statistics for an organization
     */
    public function getTeamStats(string $organizationId, Carbon $startDate, Carbon $endDate): array
    {
        $sessions = FocusSession::forOrganization($organizationId)
            ->forDateRange($startDate, $endDate)
            ->get();

        if ($sessions->isEmpty()) {
            return [
                'total_team_sessions' => 0,
                'total_team_focus_hours' => 0,
                'average_team_focus_score' => 0,
                'total_deep_work_sessions' => 0,
                'active_members' => 0,
                'top_team_apps' => [],
                'team_productivity_trend' => [],
            ];
        }

        // Calculate totals
        $totalMinutes = $sessions->sum('duration_minutes');
        $totalHours = round($totalMinutes / 60, 1);
        $avgScore = round($sessions->avg('focus_score'), 1);
        $deepWorkCount = $sessions->filter->isDeepWork()->count();

        // Count active members (members with at least one session)
        $activeMembers = $sessions->pluck('user_id')->unique()->count();

        // Top apps across the team
        $topApps = $sessions->groupBy('primary_app')
            ->map->count()
            ->sortDesc()
            ->take(10)
            ->map(fn ($count, $app) => [
                'app_name' => $app,
                'session_count' => $count,
                'percentage' => round(($count / $sessions->count()) * 100, 1),
            ])
            ->values()
            ->toArray();

        // Productivity trend (daily average scores)
        $dailyTrend = $sessions->groupBy(function ($session) {
            return $session->start_time->format('Y-m-d');
        })->map(function ($daySessions, $date) {
            return [
                'date' => $date,
                'average_score' => round($daySessions->avg('focus_score'), 1),
                'session_count' => $daySessions->count(),
            ];
        })->sortBy('date')->values()->toArray();

        return [
            'total_team_sessions' => $sessions->count(),
            'total_team_focus_hours' => $totalHours,
            'average_team_focus_score' => $avgScore,
            'total_deep_work_sessions' => $deepWorkCount,
            'deep_work_percentage' => $sessions->count() > 0 ? round(($deepWorkCount / $sessions->count()) * 100, 1) : 0,
            'active_members' => $activeMembers,
            'top_team_apps' => $topApps,
            'team_productivity_trend' => $dailyTrend,
        ];
    }

    /**
     * Get member-level statistics (anonymized with ranking)
     */
    public function getMemberRankings(string $organizationId, Carbon $startDate, Carbon $endDate): array
    {
        $sessions = FocusSession::forOrganization($organizationId)
            ->forDateRange($startDate, $endDate)
            ->get();

        if ($sessions->isEmpty()) {
            return [];
        }

        // Group by member and calculate stats
        $memberStats = $sessions->groupBy('user_id')
            ->map(function ($memberSessions, $userId) {
                $totalMinutes = $memberSessions->sum('duration_minutes');
                $avgScore = round($memberSessions->avg('focus_score'), 1);
                $deepWorkCount = $memberSessions->filter->isDeepWork()->count();

                // Get member name (or anonymize)
                $member = Member::where('user_id', $userId)->first();
                $memberName = $member ? $member->user->name : 'Unknown';

                return [
                    'member_name' => $memberName, // Can be anonymized as "Member A", "Member B", etc. if privacy is required
                    'total_sessions' => $memberSessions->count(),
                    'total_focus_hours' => round($totalMinutes / 60, 1),
                    'average_focus_score' => $avgScore,
                    'deep_work_sessions' => $deepWorkCount,
                ];
            })
            ->sortByDesc('average_focus_score')
            ->values()
            ->toArray();

        // Add rankings
        foreach ($memberStats as $index => $member) {
            $memberStats[$index]['rank'] = $index + 1;
        }

        return $memberStats;
    }

    /**
     * Get team heatmap (aggregated across all members)
     */
    public function getTeamHeatmap(string $organizationId, Carbon $startDate, Carbon $endDate): array
    {
        $sessions = FocusSession::forOrganization($organizationId)
            ->forDateRange($startDate, $endDate)
            ->get();

        $heatmap = [];

        foreach ($sessions as $session) {
            $date = $session->start_time->format('Y-m-d');
            $hour = (int) $session->start_time->format('H');

            if (!isset($heatmap[$date])) {
                $heatmap[$date] = array_fill(0, 24, []);
            }

            $heatmap[$date][$hour][] = $session->focus_score;
        }

        // Calculate average scores for each hour
        foreach ($heatmap as $date => $hours) {
            foreach ($hours as $hour => $scores) {
                if (empty($scores)) {
                    $heatmap[$date][$hour] = null;
                } else {
                    $heatmap[$date][$hour] = round(array_sum($scores) / count($scores), 1);
                }
            }
        }

        return $heatmap;
    }

    /**
     * Get team productive hours (best hours for the team)
     */
    public function getTeamProductiveHours(string $organizationId, Carbon $startDate, Carbon $endDate): array
    {
        $sessions = FocusSession::forOrganization($organizationId)
            ->forDateRange($startDate, $endDate)
            ->get();

        $hourlyStats = $sessions->groupBy(function ($session) {
            return (int) $session->start_time->format('H');
        })->map(function ($group, $hour) {
            return [
                'hour' => $hour,
                'session_count' => $group->count(),
                'average_score' => round($group->avg('focus_score'), 1),
                'total_minutes' => $group->sum('duration_minutes'),
            ];
        })->sortByDesc('average_score')->values()->toArray();

        return $hourlyStats;
    }

    /**
     * Get team focus distribution by time of day
     */
    public function getTeamFocusDistribution(string $organizationId, Carbon $startDate, Carbon $endDate): array
    {
        $sessions = FocusSession::forOrganization($organizationId)
            ->forDateRange($startDate, $endDate)
            ->get();

        // Group into time periods
        $distribution = [
            'morning' => ['label' => 'Morning (6-12)', 'sessions' => 0, 'hours' => 0],
            'afternoon' => ['label' => 'Afternoon (12-18)', 'sessions' => 0, 'hours' => 0],
            'evening' => ['label' => 'Evening (18-24)', 'sessions' => 0, 'hours' => 0],
            'night' => ['label' => 'Night (0-6)', 'sessions' => 0, 'hours' => 0],
        ];

        foreach ($sessions as $session) {
            $hour = (int) $session->start_time->format('H');
            $minutes = $session->duration_minutes;

            if ($hour >= 6 && $hour < 12) {
                $distribution['morning']['sessions']++;
                $distribution['morning']['hours'] += $minutes;
            } elseif ($hour >= 12 && $hour < 18) {
                $distribution['afternoon']['sessions']++;
                $distribution['afternoon']['hours'] += $minutes;
            } elseif ($hour >= 18 && $hour < 24) {
                $distribution['evening']['sessions']++;
                $distribution['evening']['hours'] += $minutes;
            } else {
                $distribution['night']['sessions']++;
                $distribution['night']['hours'] += $minutes;
            }
        }

        // Convert minutes to hours
        foreach ($distribution as $key => $data) {
            $distribution[$key]['hours'] = round($data['hours'] / 60, 1);
        }

        return $distribution;
    }

    /**
     * Get team insights and recommendations
     */
    public function getTeamInsights(string $organizationId, Carbon $startDate, Carbon $endDate): array
    {
        $stats = $this->getTeamStats($organizationId, $startDate, $endDate);
        $productiveHours = $this->getTeamProductiveHours($organizationId, $startDate, $endDate);

        $insights = [];

        // Insight 1: Participation rate
        $totalMembers = Member::where('organization_id', $organizationId)->count();
        $participationRate = $totalMembers > 0 ? round(($stats['active_members'] / $totalMembers) * 100, 1) : 0;

        if ($participationRate < 50) {
            $insights[] = [
                'type' => 'warning',
                'title' => 'Low Participation',
                'message' => "Only {$participationRate}% of team members are actively using focus tracking. Consider promoting the benefits of focus sessions.",
            ];
        }

        // Insight 2: Team focus score
        if ($stats['average_team_focus_score'] >= 80) {
            $insights[] = [
                'type' => 'success',
                'title' => 'Excellent Team Focus',
                'message' => "Your team's average focus score of {$stats['average_team_focus_score']}/100 is excellent! Keep up the great work.",
            ];
        } elseif ($stats['average_team_focus_score'] < 60) {
            $insights[] = [
                'type' => 'tip',
                'title' => 'Focus Improvement Opportunity',
                'message' => "Team focus score is {$stats['average_team_focus_score']}/100. Consider implementing focus time blocks or reducing meeting interruptions.",
            ];
        }

        // Insight 3: Best focus hours
        if (!empty($productiveHours)) {
            $bestHour = $productiveHours[0];
            $hourLabel = $bestHour['hour'] . ':00';
            $insights[] = [
                'type' => 'info',
                'title' => 'Peak Productivity Hour',
                'message' => "The team is most focused around {$hourLabel} with an average score of {$bestHour['average_score']}/100. Schedule important deep work during this time.",
            ];
        }

        // Insight 4: Deep work percentage
        if ($stats['deep_work_percentage'] < 30) {
            $insights[] = [
                'type' => 'tip',
                'title' => 'Increase Deep Work',
                'message' => "Only {$stats['deep_work_percentage']}% of sessions qualify as deep work. Encourage longer, uninterrupted focus sessions.",
            ];
        }

        return $insights;
    }
}
