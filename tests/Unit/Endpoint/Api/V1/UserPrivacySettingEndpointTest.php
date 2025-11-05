<?php

declare(strict_types=1);

namespace Tests\Unit\Endpoint\Api\V1;

use App\Enums\ActivityTrackingLevel;
use App\Http\Controllers\Api\V1\UserPrivacySettingController;
use App\Models\PrivacyConsentLog;
use App\Models\User;
use App\Models\UserPrivacySetting;
use Laravel\Passport\Passport;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(UserPrivacySettingController::class)]
class UserPrivacySettingEndpointTest extends ApiEndpointTestAbstract
{
    public function test_show_fails_when_not_authenticated(): void
    {
        // Act
        $response = $this->getJson(route('api.v1.user-privacy-settings.show'));

        // Assert
        $response->assertUnauthorized();
    }

    public function test_show_returns_user_privacy_settings(): void
    {
        // Arrange
        $user = User::factory()->create();
        $settings = UserPrivacySetting::factory()->forUser($user)->create();
        Passport::actingAs($user);

        // Act
        $response = $this->getJson(route('api.v1.user-privacy-settings.show'));

        // Assert
        $response->assertSuccessful();
        $response->assertJson([
            'data' => [
                'id' => $settings->getKey(),
                'user_id' => $user->getKey(),
                'tracking_level' => ActivityTrackingLevel::Manual->value,
                'tracking_level_label' => 'Manual Tracking',
                'screenshot_enabled' => false,
                'app_tracking_enabled' => false,
                'encryption_enabled' => true,
                'data_retention_days' => 90,
            ],
        ]);
    }

    public function test_show_creates_default_settings_if_not_exist(): void
    {
        // Arrange
        $user = User::factory()->create();
        Passport::actingAs($user);

        // Act
        $response = $this->getJson(route('api.v1.user-privacy-settings.show'));

        // Assert
        $response->assertSuccessful();
        $response->assertJson([
            'data' => [
                'user_id' => $user->getKey(),
                'tracking_level' => ActivityTrackingLevel::Manual->value,
                'tracking_level_label' => 'Manual Tracking',
                'screenshot_enabled' => false,
                'encryption_enabled' => true,
            ],
        ]);

        // Verify settings were created in database
        $this->assertDatabaseHas('user_privacy_settings', [
            'user_id' => $user->getKey(),
        ]);
    }

    public function test_update_fails_when_not_authenticated(): void
    {
        // Act
        $response = $this->putJson(route('api.v1.user-privacy-settings.update'), [
            'tracking_level' => ActivityTrackingLevel::Monitoring->value,
        ]);

        // Assert
        $response->assertUnauthorized();
    }

    public function test_update_updates_user_privacy_settings(): void
    {
        // Arrange
        $user = User::factory()->create();
        UserPrivacySetting::factory()->forUser($user)->create();
        Passport::actingAs($user);

        // Act
        $response = $this->putJson(route('api.v1.user-privacy-settings.update'), [
            'tracking_level' => ActivityTrackingLevel::Monitoring->value,
            'app_tracking_enabled' => true,
            'data_retention_days' => 30,
        ]);

        // Assert
        $response->assertSuccessful();
        $response->assertJson([
            'data' => [
                'tracking_level' => ActivityTrackingLevel::Monitoring->value,
                'app_tracking_enabled' => true,
                'data_retention_days' => 30,
            ],
        ]);

        $this->assertDatabaseHas('user_privacy_settings', [
            'user_id' => $user->getKey(),
            'tracking_level' => ActivityTrackingLevel::Monitoring->value,
            'app_tracking_enabled' => true,
            'data_retention_days' => 30,
        ]);
    }

    public function test_update_creates_consent_logs(): void
    {
        // Arrange
        $user = User::factory()->create();
        UserPrivacySetting::factory()->forUser($user)->create();
        Passport::actingAs($user);

        // Act
        $response = $this->putJson(route('api.v1.user-privacy-settings.update'), [
            'tracking_level' => ActivityTrackingLevel::Monitoring->value,
            'app_tracking_enabled' => true,
            'reason' => 'Enabling monitoring for productivity insights',
        ]);

        // Assert
        $response->assertSuccessful();

        $logs = PrivacyConsentLog::where('user_id', $user->getKey())->get();
        $this->assertCount(2, $logs); // tracking_level and app_tracking_enabled

        $trackingLevelLog = $logs->firstWhere('setting_changed', 'tracking_level');
        $this->assertNotNull($trackingLevelLog);
        $this->assertSame('0', $trackingLevelLog->old_value);
        $this->assertSame('2', $trackingLevelLog->new_value);
        $this->assertSame('Enabling monitoring for productivity insights', $trackingLevelLog->reason);
    }

    public function test_update_validates_tracking_level(): void
    {
        // Arrange
        $user = User::factory()->create();
        UserPrivacySetting::factory()->forUser($user)->create();
        Passport::actingAs($user);

        // Act
        $response = $this->putJson(route('api.v1.user-privacy-settings.update'), [
            'tracking_level' => 5, // Invalid level
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('tracking_level');
    }

    public function test_update_validates_data_retention_days(): void
    {
        // Arrange
        $user = User::factory()->create();
        UserPrivacySetting::factory()->forUser($user)->create();
        Passport::actingAs($user);

        // Act - test minimum
        $response = $this->putJson(route('api.v1.user-privacy-settings.update'), [
            'data_retention_days' => 0,
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('data_retention_days');
    }

    public function test_update_validates_maximum_data_retention(): void
    {
        // Arrange
        $user = User::factory()->create();
        UserPrivacySetting::factory()->forUser($user)->create();
        Passport::actingAs($user);

        // Act - test maximum (> 10 years)
        $response = $this->putJson(route('api.v1.user-privacy-settings.update'), [
            'data_retention_days' => 4000,
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('data_retention_days');
    }

    public function test_update_validates_boolean_fields(): void
    {
        // Arrange
        $user = User::factory()->create();
        UserPrivacySetting::factory()->forUser($user)->create();
        Passport::actingAs($user);

        // Act
        $response = $this->putJson(route('api.v1.user-privacy-settings.update'), [
            'screenshot_enabled' => 'not-a-boolean',
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('screenshot_enabled');
    }

    public function test_consent_history_fails_when_not_authenticated(): void
    {
        // Act
        $response = $this->getJson(route('api.v1.user-privacy-settings.consent-history'));

        // Assert
        $response->assertUnauthorized();
    }

    public function test_consent_history_returns_user_consent_logs(): void
    {
        // Arrange
        $user = User::factory()->create();
        Passport::actingAs($user);

        // Create multiple consent logs
        PrivacyConsentLog::factory()
            ->forUser($user)
            ->forSetting('tracking_level', '0', '1')
            ->withReason('First change')
            ->consentedAt(now()->subHours(2))
            ->create();

        PrivacyConsentLog::factory()
            ->forUser($user)
            ->forSetting('tracking_level', '1', '2')
            ->withReason('Second change')
            ->consentedAt(now()->subHour())
            ->create();

        // Act
        $response = $this->getJson(route('api.v1.user-privacy-settings.consent-history'));

        // Assert
        $response->assertSuccessful();
        $response->assertJsonCount(2, 'data');

        // Should be ordered newest first
        $data = $response->json('data');
        $this->assertSame('Second change', $data[0]['reason']);
        $this->assertSame('First change', $data[1]['reason']);
    }

    public function test_consent_history_limits_to_50_entries(): void
    {
        // Arrange
        $user = User::factory()->create();
        Passport::actingAs($user);

        // Create 60 consent logs
        for ($i = 0; $i < 60; $i++) {
            PrivacyConsentLog::factory()
                ->forUser($user)
                ->consentedAt(now()->subMinutes(60 - $i))
                ->create();
        }

        // Act
        $response = $this->getJson(route('api.v1.user-privacy-settings.consent-history'));

        // Assert
        $response->assertSuccessful();
        $response->assertJsonCount(50, 'data'); // Limited to 50
    }

    public function test_data_collection_status_fails_when_not_authenticated(): void
    {
        // Act
        $response = $this->getJson(route('api.v1.user-privacy-settings.data-collection-status'));

        // Assert
        $response->assertUnauthorized();
    }

    public function test_data_collection_status_returns_correct_status(): void
    {
        // Arrange
        $user = User::factory()->create();
        UserPrivacySetting::factory()
            ->forUser($user)
            ->withMonitoring()
            ->create([
                'geolocation_enabled' => true,
            ]);
        Passport::actingAs($user);

        // Act
        $response = $this->getJson(route('api.v1.user-privacy-settings.data-collection-status'));

        // Assert
        $response->assertSuccessful();
        $response->assertJson([
            'data' => [
                'manual_time_entries' => true,
                'idle_detection' => true,
                'app_names' => true,
                'urls' => true,
                'keyboard_mouse_activity' => false,
                'screenshots' => false,
                'geolocation' => true,
            ],
        ]);
    }

    public function test_data_collection_status_for_manual_tracking(): void
    {
        // Arrange
        $user = User::factory()->create();
        UserPrivacySetting::factory()->forUser($user)->create(); // Manual level
        Passport::actingAs($user);

        // Act
        $response = $this->getJson(route('api.v1.user-privacy-settings.data-collection-status'));

        // Assert
        $response->assertSuccessful();
        $response->assertJson([
            'data' => [
                'manual_time_entries' => true,
                'idle_detection' => false,
                'app_names' => false,
                'urls' => false,
                'keyboard_mouse_activity' => false,
                'screenshots' => false,
                'geolocation' => false,
            ],
        ]);
    }

    public function test_update_accepts_partial_updates(): void
    {
        // Arrange
        $user = User::factory()->create();
        UserPrivacySetting::factory()
            ->forUser($user)
            ->create([
                'tracking_level' => ActivityTrackingLevel::Monitoring,
                'app_tracking_enabled' => true,
                'data_retention_days' => 90,
            ]);
        Passport::actingAs($user);

        // Act - only update data retention
        $response = $this->putJson(route('api.v1.user-privacy-settings.update'), [
            'data_retention_days' => 30,
        ]);

        // Assert
        $response->assertSuccessful();
        $settings = UserPrivacySetting::where('user_id', $user->getKey())->first();
        $this->assertSame(ActivityTrackingLevel::Monitoring, $settings->tracking_level); // Unchanged
        $this->assertTrue($settings->app_tracking_enabled); // Unchanged
        $this->assertSame(30, $settings->data_retention_days); // Changed
    }
}
