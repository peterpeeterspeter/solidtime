<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\AppActivity;
use App\Models\Member;
use App\Models\Organization;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Laravel\Passport\Passport;
use Tests\TestCase;

class ActivitySnapshotControllerTest extends TestCase
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

    public function test_store_creates_activity_snapshot(): void
    {
        // Arrange
        $snapshot = [
            'app_name' => 'Visual Studio Code',
            'window_title' => 'index.php - Solidtime',
            'active_seconds' => 8,
            'idle_seconds' => 2,
            'keyboard_count' => 45,
            'mouse_count' => 82,
            'timestamp' => now()->timestamp,
            'client_version' => '1.0.0',
            'platform' => 'macos',
        ];

        $encryptedData = base64_encode(json_encode($snapshot));

        // Act
        $response = $this->postJson('/api/v1/activity-snapshots', [
            'encrypted_data' => $encryptedData,
        ]);

        // Assert
        $response->assertStatus(201);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('app_activities', [
            'user_id' => $this->user->id,
            'organization_id' => $this->organization->id,
            'app_name' => 'Visual Studio Code',
            'active_seconds' => 8,
            'idle_seconds' => 2,
            'keyboard_count' => 45,
            'mouse_count' => 82,
            'platform' => 'macos',
        ]);
    }

    public function test_store_encrypts_sensitive_data(): void
    {
        // Arrange
        $snapshot = [
            'app_name' => 'Google Chrome',
            'window_title' => 'Secret Project - GitHub',
            'active_seconds' => 10,
            'timestamp' => now()->timestamp,
        ];

        $encryptedData = base64_encode(json_encode($snapshot));

        // Act
        $this->postJson('/api/v1/activity-snapshots', [
            'encrypted_data' => $encryptedData,
        ]);

        // Assert
        $activity = AppActivity::where('user_id', $this->user->id)->first();
        $this->assertNotNull($activity);
        $this->assertEquals('Secret Project - GitHub', $activity->window_title);

        // Verify data is encrypted in database
        $this->assertNotEquals('Secret Project - GitHub', $activity->getRawOriginal('encrypted_data'));
    }

    public function test_store_validates_required_fields(): void
    {
        // Act
        $response = $this->postJson('/api/v1/activity-snapshots', []);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['encrypted_data']);
    }

    public function test_store_requires_authentication(): void
    {
        // Arrange
        Passport::actingAs(null);

        // Act
        $response = $this->postJson('/api/v1/activity-snapshots', [
            'encrypted_data' => base64_encode(json_encode(['app_name' => 'Test'])),
        ]);

        // Assert
        $response->assertStatus(401);
    }

    public function test_daily_summary_returns_aggregated_data(): void
    {
        // Arrange
        $date = Carbon::parse('2025-11-05');

        // Create activities for the day
        AppActivity::factory()->count(10)->create([
            'user_id' => $this->user->id,
            'organization_id' => $this->organization->id,
            'recorded_at' => $date->setTime(9, 0),
            'active_seconds' => 8,
            'idle_seconds' => 2,
        ]);

        // Act
        $response = $this->getJson('/api/v1/activity-snapshots/daily-summary?date=2025-11-05');

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'total_seconds',
                'active_seconds',
                'idle_seconds',
                'productivity_score',
                'top_apps',
                'hourly_distribution',
            ],
        ]);

        $this->assertEquals(100, $response->json('data.total_seconds')); // 10 activities * 10 seconds
        $this->assertEquals(80, $response->json('data.active_seconds')); // 10 activities * 8 seconds
        $this->assertEquals(80, $response->json('data.productivity_score'));
    }

    public function test_daily_summary_defaults_to_today(): void
    {
        // Arrange
        AppActivity::factory()->count(5)->create([
            'user_id' => $this->user->id,
            'organization_id' => $this->organization->id,
            'recorded_at' => now(),
            'active_seconds' => 10,
        ]);

        // Act
        $response = $this->getJson('/api/v1/activity-snapshots/daily-summary');

        // Assert
        $response->assertStatus(200);
        $this->assertNotEmpty($response->json('data'));
    }

    public function test_weekly_summary_returns_aggregated_data(): void
    {
        // Arrange
        $startDate = Carbon::parse('2025-11-03'); // Monday

        // Create activities for the week
        for ($day = 0; $day < 5; $day++) {
            AppActivity::factory()->count(6)->create([
                'user_id' => $this->user->id,
                'organization_id' => $this->organization->id,
                'recorded_at' => $startDate->copy()->addDays($day)->setTime(9, 0),
                'active_seconds' => 8,
            ]);
        }

        // Act
        $response = $this->getJson('/api/v1/activity-snapshots/weekly-summary?start_date=2025-11-03');

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'total_seconds',
                'active_seconds',
                'idle_seconds',
                'productivity_score',
                'daily_breakdown',
            ],
        ]);

        $this->assertCount(5, $response->json('data.daily_breakdown'));
    }

    public function test_hourly_returns_hourly_aggregation(): void
    {
        // Arrange
        $date = Carbon::parse('2025-11-05 09:00:00');

        AppActivity::factory()->count(6)->create([
            'user_id' => $this->user->id,
            'organization_id' => $this->organization->id,
            'recorded_at' => $date,
            'active_seconds' => 8,
        ]);

        AppActivity::factory()->count(6)->create([
            'user_id' => $this->user->id,
            'organization_id' => $this->organization->id,
            'recorded_at' => $date->copy()->addHour(),
            'active_seconds' => 7,
        ]);

        // Act
        $response = $this->getJson('/api/v1/activity-snapshots/hourly?date=2025-11-05');

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '2025-11-05 09:00' => [
                    'total_seconds',
                    'active_seconds',
                    'top_apps',
                ],
            ],
        ]);
    }

    public function test_focus_sessions_returns_detected_sessions(): void
    {
        // Arrange
        $date = Carbon::parse('2025-11-05 09:00:00');

        // Create a 30-minute focus session
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
        $response = $this->getJson('/api/v1/activity-snapshots/focus-sessions?date=2025-11-05');

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'start_time',
                    'end_time',
                    'duration',
                    'primary_app',
                    'app_switches',
                    'focus_score',
                ],
            ],
        ]);

        $this->assertNotEmpty($response->json('data'));
    }

    public function test_timeline_returns_chronological_activities(): void
    {
        // Arrange
        $date = Carbon::parse('2025-11-05');

        $activity1 = AppActivity::factory()->create([
            'user_id' => $this->user->id,
            'organization_id' => $this->organization->id,
            'recorded_at' => $date->copy()->setTime(9, 0),
            'app_name' => 'Visual Studio Code',
        ]);

        $activity2 = AppActivity::factory()->create([
            'user_id' => $this->user->id,
            'organization_id' => $this->organization->id,
            'recorded_at' => $date->copy()->setTime(10, 0),
            'app_name' => 'Google Chrome',
        ]);

        // Act
        $response = $this->getJson('/api/v1/activity-snapshots/timeline?date=2025-11-05');

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'app_name',
                    'active_seconds',
                    'idle_seconds',
                    'recorded_at',
                    'productivity_score',
                ],
            ],
        ]);

        $this->assertCount(2, $response->json('data'));
    }

    public function test_timeline_does_not_expose_window_titles_by_default(): void
    {
        // Arrange
        $date = Carbon::parse('2025-11-05');

        $encryptedData = Crypt::encryptString(json_encode([
            'window_title' => 'Secret Document',
        ]));

        AppActivity::factory()->create([
            'user_id' => $this->user->id,
            'organization_id' => $this->organization->id,
            'recorded_at' => $date,
            'encrypted_data' => $encryptedData,
        ]);

        // Act
        $response = $this->getJson('/api/v1/activity-snapshots/timeline?date=2025-11-05');

        // Assert
        $response->assertStatus(200);
        $this->assertArrayNotHasKey('window_title', $response->json('data.0'));
        $this->assertArrayNotHasKey('encrypted_data', $response->json('data.0'));
    }

    public function test_destroy_deletes_activities_for_date_range(): void
    {
        // Arrange
        AppActivity::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'organization_id' => $this->organization->id,
            'recorded_at' => Carbon::parse('2025-11-05'),
        ]);

        AppActivity::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'organization_id' => $this->organization->id,
            'recorded_at' => Carbon::parse('2025-11-10'),
        ]);

        // Act
        $response = $this->deleteJson('/api/v1/activity-snapshots', [
            'start_date' => '2025-11-04',
            'end_date' => '2025-11-06',
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Verify only activities in range were deleted
        $this->assertEquals(2, AppActivity::where('user_id', $this->user->id)->count());
    }

    public function test_destroy_only_deletes_own_activities(): void
    {
        // Arrange
        $otherUser = User::factory()->create();

        AppActivity::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'organization_id' => $this->organization->id,
            'recorded_at' => Carbon::parse('2025-11-05'),
        ]);

        AppActivity::factory()->count(2)->create([
            'user_id' => $otherUser->id,
            'organization_id' => $this->organization->id,
            'recorded_at' => Carbon::parse('2025-11-05'),
        ]);

        // Act
        $response = $this->deleteJson('/api/v1/activity-snapshots', [
            'start_date' => '2025-11-04',
            'end_date' => '2025-11-06',
        ]);

        // Assert
        $response->assertStatus(200);

        // Verify only current user's activities were deleted
        $this->assertEquals(0, AppActivity::where('user_id', $this->user->id)->count());
        $this->assertEquals(2, AppActivity::where('user_id', $otherUser->id)->count());
    }

    public function test_destroy_requires_date_range(): void
    {
        // Act
        $response = $this->deleteJson('/api/v1/activity-snapshots', []);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['start_date', 'end_date']);
    }
}
