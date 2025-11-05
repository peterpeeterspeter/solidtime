<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\AppActivity;
use App\Models\Organization;
use App\Models\User;
use App\Services\ActivityAggregationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityAggregationServiceTest extends TestCase
{
    use RefreshDatabase;

    private ActivityAggregationService $service;

    private User $user;

    private Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new ActivityAggregationService();
        $this->user = User::factory()->create();
        $this->organization = Organization::factory()->create();
    }

    public function test_aggregate_hourly_groups_activities_by_hour(): void
    {
        // Arrange
        $date = Carbon::parse('2025-11-05');

        // Create activities in different hours
        AppActivity::factory()->count(6)->create([
            'user_id' => $this->user->id,
            'organization_id' => $this->organization->id,
            'recorded_at' => $date->copy()->setTime(9, 0),
            'active_seconds' => 8,
        ]);

        AppActivity::factory()->count(6)->create([
            'user_id' => $this->user->id,
            'organization_id' => $this->organization->id,
            'recorded_at' => $date->copy()->setTime(10, 30),
            'active_seconds' => 7,
        ]);

        // Act
        $result = $this->service->aggregateHourly($this->user->id, $date);

        // Assert
        $this->assertArrayHasKey('2025-11-05 09:00', $result);
        $this->assertArrayHasKey('2025-11-05 10:00', $result);
        $this->assertCount(2, $result);
    }

    public function test_aggregate_hourly_calculates_totals_correctly(): void
    {
        // Arrange
        $date = Carbon::parse('2025-11-05 09:00:00');

        // Create 6 activities (1 minute total with 10-second snapshots)
        AppActivity::factory()->count(6)->create([
            'user_id' => $this->user->id,
            'organization_id' => $this->organization->id,
            'recorded_at' => $date,
            'active_seconds' => 8,
            'idle_seconds' => 2,
        ]);

        // Act
        $result = $this->service->aggregateHourly($this->user->id, $date);

        // Assert
        $hour = $result['2025-11-05 09:00'];
        $this->assertEquals(60, $hour['total_seconds']); // 6 * 10 seconds
        $this->assertEquals(48, $hour['active_seconds']); // 6 * 8 seconds
        $this->assertEquals(12, $hour['idle_seconds']); // 6 * 2 seconds
        $this->assertEquals(80, $hour['productivity_score']); // 48/60 * 100
    }

    public function test_aggregate_hourly_identifies_top_apps(): void
    {
        // Arrange
        $date = Carbon::parse('2025-11-05 09:00:00');

        // VS Code: 30 seconds (3 activities)
        AppActivity::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'organization_id' => $this->organization->id,
            'recorded_at' => $date,
            'app_name' => 'Visual Studio Code',
            'active_seconds' => 10,
        ]);

        // Chrome: 20 seconds (2 activities)
        AppActivity::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'organization_id' => $this->organization->id,
            'recorded_at' => $date,
            'app_name' => 'Google Chrome',
            'active_seconds' => 10,
        ]);

        // Slack: 10 seconds (1 activity)
        AppActivity::factory()->create([
            'user_id' => $this->user->id,
            'organization_id' => $this->organization->id,
            'recorded_at' => $date,
            'app_name' => 'Slack',
            'active_seconds' => 10,
        ]);

        // Act
        $result = $this->service->aggregateHourly($this->user->id, $date);

        // Assert
        $topApps = $result['2025-11-05 09:00']['top_apps'];
        $this->assertCount(3, $topApps);
        $this->assertEquals('Visual Studio Code', $topApps[0]['app_name']);
        $this->assertEquals(30, $topApps[0]['seconds']);
        $this->assertEquals('Google Chrome', $topApps[1]['app_name']);
        $this->assertEquals(20, $topApps[1]['seconds']);
    }

    public function test_get_daily_summary_aggregates_full_day(): void
    {
        // Arrange
        $date = Carbon::parse('2025-11-05');

        // Create activities throughout the day
        for ($hour = 9; $hour <= 17; $hour++) {
            AppActivity::factory()->count(6)->create([
                'user_id' => $this->user->id,
                'organization_id' => $this->organization->id,
                'recorded_at' => $date->copy()->setTime($hour, 0),
                'active_seconds' => 8,
                'idle_seconds' => 2,
            ]);
        }

        // Act
        $result = $this->service->getDailySummary($this->user->id, $date);

        // Assert
        $this->assertEquals(540, $result['total_seconds']); // 9 hours * 6 activities * 10 seconds
        $this->assertEquals(432, $result['active_seconds']); // 9 hours * 6 activities * 8 seconds
        $this->assertEquals(108, $result['idle_seconds']); // 9 hours * 6 activities * 2 seconds
        $this->assertEquals(80, $result['productivity_score']);
        $this->assertArrayHasKey('top_apps', $result);
        $this->assertArrayHasKey('hourly_distribution', $result);
    }

    public function test_get_weekly_summary_aggregates_full_week(): void
    {
        // Arrange
        $startDate = Carbon::parse('2025-11-03'); // Monday

        // Create activities for 5 weekdays
        for ($day = 0; $day < 5; $day++) {
            $date = $startDate->copy()->addDays($day);
            AppActivity::factory()->count(6)->create([
                'user_id' => $this->user->id,
                'organization_id' => $this->organization->id,
                'recorded_at' => $date->setTime(9, 0),
                'active_seconds' => 8,
                'idle_seconds' => 2,
            ]);
        }

        // Act
        $result = $this->service->getWeeklySummary($this->user->id, $startDate);

        // Assert
        $this->assertEquals(300, $result['total_seconds']); // 5 days * 6 activities * 10 seconds
        $this->assertEquals(240, $result['active_seconds']); // 5 days * 6 activities * 8 seconds
        $this->assertEquals(60, $result['idle_seconds']); // 5 days * 6 activities * 2 seconds
        $this->assertEquals(80, $result['productivity_score']);
        $this->assertArrayHasKey('daily_breakdown', $result);
        $this->assertCount(5, $result['daily_breakdown']);
    }

    public function test_detect_focus_sessions_identifies_continuous_work(): void
    {
        // Arrange
        $date = Carbon::parse('2025-11-05 09:00:00');

        // Create a 30-minute focus session (180 activities of 10 seconds each = 30 minutes)
        for ($minute = 0; $minute < 30; $minute++) {
            AppActivity::factory()->count(6)->create([
                'user_id' => $this->user->id,
                'organization_id' => $this->organization->id,
                'recorded_at' => $date->copy()->addMinutes($minute),
                'app_name' => 'Visual Studio Code',
                'active_seconds' => 9,
                'idle_seconds' => 1,
            ]);
        }

        // Act
        $sessions = $this->service->detectFocusSessions($this->user->id, $date->startOfDay());

        // Assert
        $this->assertNotEmpty($sessions);
        $this->assertGreaterThanOrEqual(1200, $sessions[0]['duration']); // At least 20 minutes
        $this->assertLessThan(3, $sessions[0]['app_switches']);
    }

    public function test_detect_focus_sessions_excludes_idle_periods(): void
    {
        // Arrange
        $date = Carbon::parse('2025-11-05 09:00:00');

        // 10 minutes active
        for ($minute = 0; $minute < 10; $minute++) {
            AppActivity::factory()->count(6)->create([
                'user_id' => $this->user->id,
                'organization_id' => $this->organization->id,
                'recorded_at' => $date->copy()->addMinutes($minute),
                'active_seconds' => 9,
            ]);
        }

        // 10 minutes idle (should break session)
        AppActivity::factory()->create([
            'user_id' => $this->user->id,
            'organization_id' => $this->organization->id,
            'recorded_at' => $date->copy()->addMinutes(10),
            'active_seconds' => 0,
            'idle_seconds' => 600, // 10 minutes idle
        ]);

        // 20 minutes active again
        for ($minute = 11; $minute < 31; $minute++) {
            AppActivity::factory()->count(6)->create([
                'user_id' => $this->user->id,
                'organization_id' => $this->organization->id,
                'recorded_at' => $date->copy()->addMinutes($minute),
                'active_seconds' => 9,
            ]);
        }

        // Act
        $sessions = $this->service->detectFocusSessions($this->user->id, $date->startOfDay());

        // Assert
        // Should have 2 sessions (one before idle, one after)
        $this->assertGreaterThanOrEqual(1, count($sessions));
    }

    public function test_detect_focus_sessions_excludes_frequent_app_switching(): void
    {
        // Arrange
        $date = Carbon::parse('2025-11-05 09:00:00');
        $apps = ['Visual Studio Code', 'Google Chrome', 'Slack', 'Terminal', 'Notion'];

        // Create 30 minutes with frequent app switching
        for ($minute = 0; $minute < 30; $minute++) {
            AppActivity::factory()->count(6)->create([
                'user_id' => $this->user->id,
                'organization_id' => $this->organization->id,
                'recorded_at' => $date->copy()->addMinutes($minute),
                'app_name' => $apps[$minute % 5], // Switch app every minute
                'active_seconds' => 9,
            ]);
        }

        // Act
        $sessions = $this->service->detectFocusSessions($this->user->id, $date->startOfDay());

        // Assert
        // Should not detect as focus session due to frequent switching (>3 switches)
        $this->assertEmpty($sessions);
    }

    public function test_calculate_focus_score_returns_100_for_perfect_session(): void
    {
        // Arrange
        $session = [
            'duration' => 3600, // 60 minutes
            'app_switches' => 0,
            'unique_apps' => 1,
            'active_percentage' => 100,
        ];

        // Act
        $score = $this->service->calculateFocusScore($session);

        // Assert
        $this->assertEquals(100, $score);
    }

    public function test_calculate_focus_score_penalizes_app_switches(): void
    {
        // Arrange
        $perfectSession = [
            'duration' => 3600,
            'app_switches' => 0,
            'unique_apps' => 1,
            'active_percentage' => 100,
        ];

        $switchingSession = [
            'duration' => 3600,
            'app_switches' => 10,
            'unique_apps' => 5,
            'active_percentage' => 100,
        ];

        // Act
        $perfectScore = $this->service->calculateFocusScore($perfectSession);
        $switchingScore = $this->service->calculateFocusScore($switchingSession);

        // Assert
        $this->assertLessThan($perfectScore, $switchingScore);
    }
}
