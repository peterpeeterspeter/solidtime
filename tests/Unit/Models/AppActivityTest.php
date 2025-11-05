<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\AppActivity;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class AppActivityTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_belongs_to_a_user(): void
    {
        // Arrange
        $user = User::factory()->create();
        $activity = AppActivity::factory()->create([
            'user_id' => $user->id,
        ]);

        // Act & Assert
        $this->assertInstanceOf(User::class, $activity->user);
        $this->assertEquals($user->id, $activity->user->id);
    }

    public function test_it_belongs_to_an_organization(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $activity = AppActivity::factory()->create([
            'organization_id' => $organization->id,
        ]);

        // Act & Assert
        $this->assertInstanceOf(Organization::class, $activity->organization);
        $this->assertEquals($organization->id, $activity->organization->id);
    }

    public function test_it_can_decrypt_window_title(): void
    {
        // Arrange
        $windowTitle = 'index.php - VS Code';
        $encryptedData = Crypt::encryptString(json_encode([
            'window_title' => $windowTitle,
        ]));

        $activity = AppActivity::factory()->create([
            'encrypted_data' => $encryptedData,
        ]);

        // Act
        $decryptedTitle = $activity->window_title;

        // Assert
        $this->assertEquals($windowTitle, $decryptedTitle);
    }

    public function test_it_can_set_encrypted_data_from_array(): void
    {
        // Arrange
        $activity = AppActivity::factory()->create();
        $data = [
            'window_title' => 'Test Window',
            'url' => 'https://example.com',
        ];

        // Act
        $activity->setEncryptedDataFromArray($data);
        $activity->save();

        // Assert
        $this->assertNotEmpty($activity->encrypted_data);
        $this->assertEquals('Test Window', $activity->fresh()->window_title);
    }

    public function test_it_can_get_decrypted_data(): void
    {
        // Arrange
        $data = [
            'window_title' => 'Test Window',
            'url' => 'https://example.com',
        ];
        $activity = AppActivity::factory()->create();
        $activity->setEncryptedDataFromArray($data);

        // Act
        $decryptedData = $activity->getDecryptedDataAttribute();

        // Assert
        $this->assertEquals($data, $decryptedData);
    }

    public function test_scope_for_user_filters_by_user(): void
    {
        // Arrange
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        AppActivity::factory()->count(3)->create(['user_id' => $user1->id]);
        AppActivity::factory()->count(2)->create(['user_id' => $user2->id]);

        // Act
        $activities = AppActivity::forUser($user1->id)->get();

        // Assert
        $this->assertCount(3, $activities);
        $activities->each(fn ($activity) => $this->assertEquals($user1->id, $activity->user_id));
    }

    public function test_scope_for_organization_filters_by_organization(): void
    {
        // Arrange
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();
        AppActivity::factory()->count(3)->create(['organization_id' => $org1->id]);
        AppActivity::factory()->count(2)->create(['organization_id' => $org2->id]);

        // Act
        $activities = AppActivity::forOrganization($org1->id)->get();

        // Assert
        $this->assertCount(3, $activities);
        $activities->each(fn ($activity) => $this->assertEquals($org1->id, $activity->organization_id));
    }

    public function test_scope_for_date_range_filters_activities(): void
    {
        // Arrange
        $user = User::factory()->create();
        AppActivity::factory()->create([
            'user_id' => $user->id,
            'recorded_at' => now()->subDays(5),
        ]);
        AppActivity::factory()->create([
            'user_id' => $user->id,
            'recorded_at' => now()->subDays(2),
        ]);
        AppActivity::factory()->create([
            'user_id' => $user->id,
            'recorded_at' => now(),
        ]);

        // Act
        $activities = AppActivity::forUser($user->id)
            ->forDateRange(now()->subDays(3), now()->subDay())
            ->get();

        // Assert
        $this->assertCount(1, $activities);
    }

    public function test_scope_for_app_filters_by_app_name(): void
    {
        // Arrange
        $user = User::factory()->create();
        AppActivity::factory()->count(3)->create([
            'user_id' => $user->id,
            'app_name' => 'Visual Studio Code',
        ]);
        AppActivity::factory()->count(2)->create([
            'user_id' => $user->id,
            'app_name' => 'Google Chrome',
        ]);

        // Act
        $activities = AppActivity::forUser($user->id)
            ->forApp('Visual Studio Code')
            ->get();

        // Assert
        $this->assertCount(3, $activities);
        $activities->each(fn ($activity) => $this->assertEquals('Visual Studio Code', $activity->app_name));
    }

    public function test_scope_active_only_filters_active_activities(): void
    {
        // Arrange
        $user = User::factory()->create();
        AppActivity::factory()->count(3)->focused()->create(['user_id' => $user->id]);
        AppActivity::factory()->count(2)->idle()->create(['user_id' => $user->id]);

        // Act
        $activities = AppActivity::forUser($user->id)->activeOnly()->get();

        // Assert
        $this->assertCount(3, $activities);
        $activities->each(fn ($activity) => $this->assertGreaterThan(0, $activity->active_seconds));
    }

    public function test_scope_ordered_sorts_by_recorded_at(): void
    {
        // Arrange
        $user = User::factory()->create();
        $activity1 = AppActivity::factory()->create([
            'user_id' => $user->id,
            'recorded_at' => now()->subHours(2),
        ]);
        $activity2 = AppActivity::factory()->create([
            'user_id' => $user->id,
            'recorded_at' => now(),
        ]);
        $activity3 = AppActivity::factory()->create([
            'user_id' => $user->id,
            'recorded_at' => now()->subHour(),
        ]);

        // Act
        $activities = AppActivity::forUser($user->id)->ordered()->get();

        // Assert
        $this->assertEquals($activity1->id, $activities[0]->id);
        $this->assertEquals($activity3->id, $activities[1]->id);
        $this->assertEquals($activity2->id, $activities[2]->id);
    }

    public function test_total_seconds_attribute_returns_sum(): void
    {
        // Arrange
        $activity = AppActivity::factory()->create([
            'active_seconds' => 6,
            'idle_seconds' => 4,
        ]);

        // Act
        $total = $activity->total_seconds;

        // Assert
        $this->assertEquals(10, $total);
    }

    public function test_productivity_score_calculates_correctly(): void
    {
        // Arrange
        $activity = AppActivity::factory()->create([
            'active_seconds' => 8,
            'idle_seconds' => 2,
        ]);

        // Act
        $score = $activity->productivity_score;

        // Assert
        $this->assertEquals(80, $score);
    }

    public function test_productivity_score_returns_zero_when_no_time(): void
    {
        // Arrange
        $activity = AppActivity::factory()->create([
            'active_seconds' => 0,
            'idle_seconds' => 0,
        ]);

        // Act
        $score = $activity->productivity_score;

        // Assert
        $this->assertEquals(0, $score);
    }

    public function test_is_active_returns_true_when_active(): void
    {
        // Arrange
        $activity = AppActivity::factory()->focused()->create();

        // Act & Assert
        $this->assertTrue($activity->isActive());
    }

    public function test_is_active_returns_false_when_idle(): void
    {
        // Arrange
        $activity = AppActivity::factory()->idle()->create();

        // Act & Assert
        $this->assertFalse($activity->isActive());
    }
}
