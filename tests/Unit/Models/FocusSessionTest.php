<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\FocusSession;
use App\Models\Organization;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FocusSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_belongs_to_a_user(): void
    {
        // Arrange
        $user = User::factory()->create();
        $session = FocusSession::factory()->create([
            'user_id' => $user->id,
        ]);

        // Act & Assert
        $this->assertInstanceOf(User::class, $session->user);
        $this->assertEquals($user->id, $session->user->id);
    }

    public function test_it_belongs_to_an_organization(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $session = FocusSession::factory()->create([
            'organization_id' => $organization->id,
        ]);

        // Act & Assert
        $this->assertInstanceOf(Organization::class, $session->organization);
        $this->assertEquals($organization->id, $session->organization->id);
    }

    public function test_scope_for_user_filters_by_user(): void
    {
        // Arrange
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        FocusSession::factory()->count(3)->create(['user_id' => $user1->id]);
        FocusSession::factory()->count(2)->create(['user_id' => $user2->id]);

        // Act
        $sessions = FocusSession::forUser($user1->id)->get();

        // Assert
        $this->assertCount(3, $sessions);
        $sessions->each(fn ($session) => $this->assertEquals($user1->id, $session->user_id));
    }

    public function test_scope_for_organization_filters_by_organization(): void
    {
        // Arrange
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();
        FocusSession::factory()->count(3)->create(['organization_id' => $org1->id]);
        FocusSession::factory()->count(2)->create(['organization_id' => $org2->id]);

        // Act
        $sessions = FocusSession::forOrganization($org1->id)->get();

        // Assert
        $this->assertCount(3, $sessions);
        $sessions->each(fn ($session) => $this->assertEquals($org1->id, $session->organization_id));
    }

    public function test_scope_for_date_range_filters_sessions(): void
    {
        // Arrange
        $user = User::factory()->create();
        FocusSession::factory()->create([
            'user_id' => $user->id,
            'start_time' => Carbon::parse('2025-11-01'),
        ]);
        FocusSession::factory()->create([
            'user_id' => $user->id,
            'start_time' => Carbon::parse('2025-11-05'),
        ]);
        FocusSession::factory()->create([
            'user_id' => $user->id,
            'start_time' => Carbon::parse('2025-11-10'),
        ]);

        // Act
        $sessions = FocusSession::forUser($user->id)
            ->forDateRange(Carbon::parse('2025-11-04'), Carbon::parse('2025-11-06'))
            ->get();

        // Assert
        $this->assertCount(1, $sessions);
    }

    public function test_scope_minimum_duration_filters_sessions(): void
    {
        // Arrange
        $user = User::factory()->create();
        FocusSession::factory()->create([
            'user_id' => $user->id,
            'duration_minutes' => 15,
        ]);
        FocusSession::factory()->create([
            'user_id' => $user->id,
            'duration_minutes' => 45,
        ]);
        FocusSession::factory()->create([
            'user_id' => $user->id,
            'duration_minutes' => 90,
        ]);

        // Act
        $sessions = FocusSession::forUser($user->id)
            ->minimumDuration(30)
            ->get();

        // Assert
        $this->assertCount(2, $sessions);
    }

    public function test_scope_minimum_score_filters_sessions(): void
    {
        // Arrange
        $user = User::factory()->create();
        FocusSession::factory()->create([
            'user_id' => $user->id,
            'focus_score' => 40,
        ]);
        FocusSession::factory()->create([
            'user_id' => $user->id,
            'focus_score' => 70,
        ]);
        FocusSession::factory()->create([
            'user_id' => $user->id,
            'focus_score' => 90,
        ]);

        // Act
        $sessions = FocusSession::forUser($user->id)
            ->minimumScore(60)
            ->get();

        // Assert
        $this->assertCount(2, $sessions);
    }

    public function test_scope_ordered_by_score_sorts_descending(): void
    {
        // Arrange
        $user = User::factory()->create();
        $session1 = FocusSession::factory()->create([
            'user_id' => $user->id,
            'focus_score' => 50,
        ]);
        $session2 = FocusSession::factory()->create([
            'user_id' => $user->id,
            'focus_score' => 90,
        ]);
        $session3 = FocusSession::factory()->create([
            'user_id' => $user->id,
            'focus_score' => 70,
        ]);

        // Act
        $sessions = FocusSession::forUser($user->id)->orderedByScore()->get();

        // Assert
        $this->assertEquals($session2->id, $sessions[0]->id); // 90
        $this->assertEquals($session3->id, $sessions[1]->id); // 70
        $this->assertEquals($session1->id, $sessions[2]->id); // 50
    }

    public function test_scope_ordered_sorts_by_start_time(): void
    {
        // Arrange
        $user = User::factory()->create();
        $session1 = FocusSession::factory()->create([
            'user_id' => $user->id,
            'start_time' => Carbon::parse('2025-11-05 10:00'),
        ]);
        $session2 = FocusSession::factory()->create([
            'user_id' => $user->id,
            'start_time' => Carbon::parse('2025-11-05 08:00'),
        ]);
        $session3 = FocusSession::factory()->create([
            'user_id' => $user->id,
            'start_time' => Carbon::parse('2025-11-05 14:00'),
        ]);

        // Act
        $sessions = FocusSession::forUser($user->id)->ordered()->get();

        // Assert
        $this->assertEquals($session2->id, $sessions[0]->id); // 08:00
        $this->assertEquals($session1->id, $sessions[1]->id); // 10:00
        $this->assertEquals($session3->id, $sessions[2]->id); // 14:00
    }

    public function test_focus_quality_attribute_returns_correct_category(): void
    {
        // Arrange
        $excellentSession = FocusSession::factory()->create(['focus_score' => 90]);
        $goodSession = FocusSession::factory()->create(['focus_score' => 70]);
        $fairSession = FocusSession::factory()->create(['focus_score' => 50]);
        $poorSession = FocusSession::factory()->create(['focus_score' => 30]);

        // Assert
        $this->assertEquals('excellent', $excellentSession->focus_quality);
        $this->assertEquals('good', $goodSession->focus_quality);
        $this->assertEquals('fair', $fairSession->focus_quality);
        $this->assertEquals('poor', $poorSession->focus_quality);
    }

    public function test_is_deep_work_returns_true_for_qualifying_session(): void
    {
        // Arrange
        $session = FocusSession::factory()->create([
            'duration_minutes' => 60,
            'focus_score' => 85,
            'app_switches' => 2,
        ]);

        // Act & Assert
        $this->assertTrue($session->isDeepWork());
    }

    public function test_is_deep_work_returns_false_for_short_session(): void
    {
        // Arrange
        $session = FocusSession::factory()->create([
            'duration_minutes' => 30,
            'focus_score' => 85,
            'app_switches' => 2,
        ]);

        // Act & Assert
        $this->assertFalse($session->isDeepWork());
    }

    public function test_is_deep_work_returns_false_for_low_score(): void
    {
        // Arrange
        $session = FocusSession::factory()->create([
            'duration_minutes' => 60,
            'focus_score' => 50,
            'app_switches' => 2,
        ]);

        // Act & Assert
        $this->assertFalse($session->isDeepWork());
    }

    public function test_is_deep_work_returns_false_for_many_switches(): void
    {
        // Arrange
        $session = FocusSession::factory()->create([
            'duration_minutes' => 60,
            'focus_score' => 85,
            'app_switches' => 5,
        ]);

        // Act & Assert
        $this->assertFalse($session->isDeepWork());
    }

    public function test_formatted_duration_attribute_formats_correctly(): void
    {
        // Arrange
        $session1 = FocusSession::factory()->create(['duration_minutes' => 45]);
        $session2 = FocusSession::factory()->create(['duration_minutes' => 90]);
        $session3 = FocusSession::factory()->create(['duration_minutes' => 125]);

        // Assert
        $this->assertEquals('45m', $session1->formatted_duration);
        $this->assertEquals('1h 30m', $session2->formatted_duration);
        $this->assertEquals('2h 5m', $session3->formatted_duration);
    }

    public function test_apps_used_is_cast_to_array(): void
    {
        // Arrange
        $apps = ['Visual Studio Code', 'Terminal', 'Google Chrome'];
        $session = FocusSession::factory()->create([
            'apps_used' => $apps,
        ]);

        // Act
        $retrieved = $session->fresh();

        // Assert
        $this->assertIsArray($retrieved->apps_used);
        $this->assertEquals($apps, $retrieved->apps_used);
    }
}
