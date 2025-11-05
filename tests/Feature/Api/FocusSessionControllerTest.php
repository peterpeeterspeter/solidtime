<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\AppActivity;
use App\Models\FocusSession;
use App\Models\Member;
use App\Models\Organization;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class FocusSessionControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->organization = Organization::factory()->create();
        Member::factory()->create([
            'user_id' => $this->user->id,
            'organization_id' => $this->organization->id,
        ]);

        Passport::actingAs($this->user);
    }

    public function test_index_returns_focus_sessions(): void
    {
        // Arrange
        FocusSession::factory()->count(5)->create([
            'user_id' => $this->user->id,
            'organization_id' => $this->organization->id,
            'start_time' => now(),
        ]);

        // Act
        $response = $this->getJson('/api/v1/focus-sessions');

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'user_id',
                    'start_time',
                    'end_time',
                    'duration_minutes',
                    'focus_score',
                    'apps_used',
                ],
            ],
        ]);
        $this->assertCount(5, $response->json('data'));
    }

    public function test_index_filters_by_date_range(): void
    {
        // Arrange
        FocusSession::factory()->create([
            'user_id' => $this->user->id,
            'organization_id' => $this->organization->id,
            'start_time' => Carbon::parse('2025-11-01'),
        ]);
        FocusSession::factory()->create([
            'user_id' => $this->user->id,
            'organization_id' => $this->organization->id,
            'start_time' => Carbon::parse('2025-11-05'),
        ]);
        FocusSession::factory()->create([
            'user_id' => $this->user->id,
            'organization_id' => $this->organization->id,
            'start_time' => Carbon::parse('2025-11-10'),
        ]);

        // Act
        $response = $this->getJson('/api/v1/focus-sessions?start_date=2025-11-04&end_date=2025-11-06');

        // Assert
        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    public function test_stats_returns_focus_statistics(): void
    {
        // Arrange
        FocusSession::factory()->count(3)->excellent()->create([
            'user_id' => $this->user->id,
            'organization_id' => $this->organization->id,
            'start_time' => now(),
        ]);

        // Act
        $response = $this->getJson('/api/v1/focus-sessions/stats');

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'total_sessions',
                'total_focus_minutes',
                'total_focus_hours',
                'average_duration_minutes',
                'average_focus_score',
                'deep_work_sessions',
                'top_focus_apps',
            ],
        ]);

        $this->assertEquals(3, $response->json('data.total_sessions'));
    }

    public function test_heatmap_returns_hourly_data(): void
    {
        // Arrange
        FocusSession::factory()->create([
            'user_id' => $this->user->id,
            'organization_id' => $this->organization->id,
            'start_time' => Carbon::parse('2025-11-05 09:00'),
            'focus_score' => 85,
        ]);
        FocusSession::factory()->create([
            'user_id' => $this->user->id,
            'organization_id' => $this->organization->id,
            'start_time' => Carbon::parse('2025-11-05 14:00'),
            'focus_score' => 92,
        ]);

        // Act
        $response = $this->getJson('/api/v1/focus-sessions/heatmap?start_date=2025-11-05&end_date=2025-11-05');

        // Assert
        $response->assertStatus(200);
        $this->assertArrayHasKey('2025-11-05', $response->json('data'));
    }

    public function test_streaks_returns_current_and_longest_streak(): void
    {
        // Arrange
        // Create consecutive daily focus sessions
        for ($day = 0; $day < 5; $day++) {
            FocusSession::factory()->create([
                'user_id' => $this->user->id,
                'organization_id' => $this->organization->id,
                'start_time' => now()->subDays(4 - $day)->setHour(9),
            ]);
        }

        // Act
        $response = $this->getJson('/api/v1/focus-sessions/streaks');

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'current_streak',
                'longest_streak',
            ],
        ]);
    }

    public function test_detect_creates_focus_sessions_from_activity_data(): void
    {
        // Arrange
        $date = Carbon::parse('2025-11-05 09:00:00');

        // Create a 30-minute focus session worth of activity data
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
        $response = $this->postJson('/api/v1/focus-sessions/detect', [
            'date' => '2025-11-05',
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data',
            'message',
            'count',
        ]);

        $this->assertGreaterThan(0, $response->json('count'));
        $this->assertDatabaseHas('focus_sessions', [
            'user_id' => $this->user->id,
        ]);
    }

    public function test_detect_requires_date(): void
    {
        // Act
        $response = $this->postJson('/api/v1/focus-sessions/detect', []);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['date']);
    }

    public function test_show_returns_single_focus_session(): void
    {
        // Arrange
        $session = FocusSession::factory()->create([
            'user_id' => $this->user->id,
            'organization_id' => $this->organization->id,
        ]);

        // Act
        $response = $this->getJson("/api/v1/focus-sessions/{$session->id}");

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'id' => $session->id,
                'user_id' => $this->user->id,
            ],
        ]);
    }

    public function test_show_returns_404_for_other_users_session(): void
    {
        // Arrange
        $otherUser = User::factory()->create();
        $session = FocusSession::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        // Act
        $response = $this->getJson("/api/v1/focus-sessions/{$session->id}");

        // Assert
        $response->assertStatus(404);
    }

    public function test_destroy_deletes_focus_session(): void
    {
        // Arrange
        $session = FocusSession::factory()->create([
            'user_id' => $this->user->id,
            'organization_id' => $this->organization->id,
        ]);

        // Act
        $response = $this->deleteJson("/api/v1/focus-sessions/{$session->id}");

        // Assert
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertDatabaseMissing('focus_sessions', [
            'id' => $session->id,
        ]);
    }

    public function test_destroy_range_deletes_sessions_in_date_range(): void
    {
        // Arrange
        FocusSession::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'organization_id' => $this->organization->id,
            'start_time' => Carbon::parse('2025-11-05'),
        ]);
        FocusSession::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'organization_id' => $this->organization->id,
            'start_time' => Carbon::parse('2025-11-10'),
        ]);

        // Act
        $response = $this->deleteJson('/api/v1/focus-sessions', [
            'start_date' => '2025-11-04',
            'end_date' => '2025-11-06',
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Verify only sessions in range were deleted
        $this->assertEquals(2, FocusSession::where('user_id', $this->user->id)->count());
    }

    public function test_destroy_range_only_deletes_own_sessions(): void
    {
        // Arrange
        $otherUser = User::factory()->create();
        FocusSession::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'organization_id' => $this->organization->id,
            'start_time' => Carbon::parse('2025-11-05'),
        ]);
        FocusSession::factory()->count(2)->create([
            'user_id' => $otherUser->id,
            'organization_id' => $this->organization->id,
            'start_time' => Carbon::parse('2025-11-05'),
        ]);

        // Act
        $response = $this->deleteJson('/api/v1/focus-sessions', [
            'start_date' => '2025-11-04',
            'end_date' => '2025-11-06',
        ]);

        // Assert
        $response->assertStatus(200);

        // Verify only current user's sessions were deleted
        $this->assertEquals(0, FocusSession::where('user_id', $this->user->id)->count());
        $this->assertEquals(2, FocusSession::where('user_id', $otherUser->id)->count());
    }

    public function test_daily_summary_returns_day_statistics(): void
    {
        // Arrange
        $date = Carbon::parse('2025-11-05');
        FocusSession::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'organization_id' => $this->organization->id,
            'start_time' => $date->copy()->setHour(9),
        ]);

        // Act
        $response = $this->getJson('/api/v1/focus-sessions/daily-summary?date=2025-11-05');

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'date',
                'sessions',
                'total_sessions',
                'total_minutes',
                'total_hours',
                'average_score',
                'deep_work_sessions',
            ],
        ]);

        $this->assertEquals('2025-11-05', $response->json('data.date'));
        $this->assertEquals(3, $response->json('data.total_sessions'));
    }

    public function test_productive_hours_returns_hourly_statistics(): void
    {
        // Arrange
        // Create sessions at different hours
        FocusSession::factory()->create([
            'user_id' => $this->user->id,
            'organization_id' => $this->organization->id,
            'start_time' => Carbon::parse('2025-11-05 09:00'),
            'focus_score' => 85,
        ]);
        FocusSession::factory()->create([
            'user_id' => $this->user->id,
            'organization_id' => $this->organization->id,
            'start_time' => Carbon::parse('2025-11-05 14:00'),
            'focus_score' => 92,
        ]);

        // Act
        $response = $this->getJson('/api/v1/focus-sessions/productive-hours');

        // Assert
        $response->assertStatus(200);
        $this->assertNotEmpty($response->json('data'));
    }

    public function test_index_requires_authentication(): void
    {
        // Arrange
        Passport::actingAs(null);

        // Act
        $response = $this->getJson('/api/v1/focus-sessions');

        // Assert
        $response->assertStatus(401);
    }
}
