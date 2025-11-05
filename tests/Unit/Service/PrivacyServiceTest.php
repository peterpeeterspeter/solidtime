<?php

declare(strict_types=1);

namespace Tests\Unit\Service;

use App\Enums\ActivityTrackingLevel;
use App\Models\PrivacyConsentLog;
use App\Models\User;
use App\Models\UserPrivacySetting;
use App\Service\PrivacyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(PrivacyService::class)]
class PrivacyServiceTest extends TestCase
{
    use RefreshDatabase;

    private PrivacyService $privacyService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->privacyService = app(PrivacyService::class);
    }

    public function test_get_or_create_settings_returns_existing_settings(): void
    {
        // Arrange
        $user = User::factory()->create();
        $existingSettings = UserPrivacySetting::factory()->forUser($user)->create();

        // Act
        $settings = $this->privacyService->getOrCreateSettings($user->getKey());

        // Assert
        $this->assertSame($existingSettings->getKey(), $settings->getKey());
    }

    public function test_get_or_create_settings_creates_default_settings_if_not_exist(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $settings = $this->privacyService->getOrCreateSettings($user->getKey());

        // Assert
        $this->assertInstanceOf(UserPrivacySetting::class, $settings);
        $this->assertSame($user->getKey(), $settings->user_id);
        $this->assertSame(ActivityTrackingLevel::Manual, $settings->tracking_level);
        $this->assertFalse($settings->screenshot_enabled);
        $this->assertFalse($settings->app_tracking_enabled);
        $this->assertTrue($settings->encryption_enabled);
        $this->assertSame(90, $settings->data_retention_days);
    }

    public function test_update_settings_updates_user_settings(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->privacyService->getOrCreateSettings($user->getKey());

        // Act
        $updatedSettings = $this->privacyService->updateSettings($user->getKey(), [
            'tracking_level' => ActivityTrackingLevel::Monitoring,
            'app_tracking_enabled' => true,
        ]);

        // Assert
        $this->assertSame(ActivityTrackingLevel::Monitoring, $updatedSettings->tracking_level);
        $this->assertTrue($updatedSettings->app_tracking_enabled);
    }

    public function test_update_settings_logs_consent_for_each_changed_field(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->privacyService->getOrCreateSettings($user->getKey());

        // Act
        $this->privacyService->updateSettings($user->getKey(), [
            'tracking_level' => ActivityTrackingLevel::Monitoring,
            'app_tracking_enabled' => true,
            'data_retention_days' => 30,
        ]);

        // Assert
        $logs = PrivacyConsentLog::where('user_id', $user->getKey())->get();
        $this->assertCount(3, $logs);

        $settingsChanged = $logs->pluck('setting_changed')->toArray();
        $this->assertContains('tracking_level', $settingsChanged);
        $this->assertContains('app_tracking_enabled', $settingsChanged);
        $this->assertContains('data_retention_days', $settingsChanged);
    }

    public function test_update_settings_includes_reason_in_consent_log(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->privacyService->getOrCreateSettings($user->getKey());
        $reason = 'Enabling monitoring for productivity insights';

        // Act
        $this->privacyService->updateSettings($user->getKey(), [
            'tracking_level' => ActivityTrackingLevel::Monitoring,
        ], $reason);

        // Assert
        $log = PrivacyConsentLog::where('user_id', $user->getKey())->first();
        $this->assertSame($reason, $log->reason);
    }

    public function test_update_settings_does_not_log_unchanged_values(): void
    {
        // Arrange
        $user = User::factory()->create();
        $settings = UserPrivacySetting::factory()
            ->forUser($user)
            ->create([
                'tracking_level' => ActivityTrackingLevel::Manual,
                'screenshot_enabled' => false,
            ]);

        // Act - update with same values
        $this->privacyService->updateSettings($user->getKey(), [
            'tracking_level' => ActivityTrackingLevel::Manual,
            'screenshot_enabled' => false,
        ]);

        // Assert - no consent logs should be created
        $this->assertSame(0, PrivacyConsentLog::where('user_id', $user->getKey())->count());
    }

    public function test_log_consent_creates_consent_log_with_all_details(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->app->instance('request', request()->merge([
            'REMOTE_ADDR' => '192.168.1.1',
            'HTTP_USER_AGENT' => 'Mozilla/5.0 Test',
        ]));

        // Act
        $this->privacyService->logConsent(
            $user->getKey(),
            'tracking_level',
            '0',
            '2',
            'Testing consent logging'
        );

        // Assert
        $log = PrivacyConsentLog::where('user_id', $user->getKey())->first();
        $this->assertNotNull($log);
        $this->assertSame('tracking_level', $log->setting_changed);
        $this->assertSame('0', $log->old_value);
        $this->assertSame('2', $log->new_value);
        $this->assertSame('Testing consent logging', $log->reason);
        $this->assertNotNull($log->consented_at);
    }

    public function test_get_consent_history_returns_most_recent_logs(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Create 60 consent logs
        for ($i = 0; $i < 60; $i++) {
            PrivacyConsentLog::factory()
                ->forUser($user)
                ->consentedAt(now()->subMinutes(60 - $i))
                ->create();
        }

        // Act
        $history = $this->privacyService->getConsentHistory($user->getKey(), 50);

        // Assert
        $this->assertCount(50, $history);
        // Should be ordered newest first
        $this->assertTrue($history[0]->consented_at->greaterThan($history[49]->consented_at));
    }

    public function test_can_enable_feature_returns_true_for_screenshot_when_level_is_monitoring(): void
    {
        // Arrange
        $user = User::factory()->create();
        UserPrivacySetting::factory()
            ->forUser($user)
            ->withMonitoring()
            ->create();

        // Act
        $canEnable = $this->privacyService->canEnableFeature($user->getKey(), 'screenshot_enabled');

        // Assert
        $this->assertTrue($canEnable);
    }

    public function test_can_enable_feature_returns_false_for_screenshot_when_level_is_idle_detection(): void
    {
        // Arrange
        $user = User::factory()->create();
        UserPrivacySetting::factory()
            ->forUser($user)
            ->withIdleDetection()
            ->create();

        // Act
        $canEnable = $this->privacyService->canEnableFeature($user->getKey(), 'screenshot_enabled');

        // Assert
        $this->assertFalse($canEnable);
    }

    public function test_can_enable_feature_returns_true_for_app_tracking_when_level_is_idle_detection(): void
    {
        // Arrange
        $user = User::factory()->create();
        UserPrivacySetting::factory()
            ->forUser($user)
            ->withIdleDetection()
            ->create();

        // Act
        $canEnable = $this->privacyService->canEnableFeature($user->getKey(), 'app_tracking_enabled');

        // Assert
        $this->assertTrue($canEnable);
    }

    public function test_can_enable_feature_returns_false_for_app_tracking_when_level_is_manual(): void
    {
        // Arrange
        $user = User::factory()->create();
        UserPrivacySetting::factory()
            ->forUser($user)
            ->create(); // Default is manual

        // Act
        $canEnable = $this->privacyService->canEnableFeature($user->getKey(), 'app_tracking_enabled');

        // Assert
        $this->assertFalse($canEnable);
    }

    public function test_get_data_collection_status_returns_correct_status_for_manual_level(): void
    {
        // Arrange
        $user = User::factory()->create();
        UserPrivacySetting::factory()->forUser($user)->create(); // Manual level

        // Act
        $status = $this->privacyService->getDataCollectionStatus($user->getKey());

        // Assert
        $this->assertTrue($status['manual_time_entries']); // Always true
        $this->assertFalse($status['idle_detection']);
        $this->assertFalse($status['app_names']);
        $this->assertFalse($status['urls']);
        $this->assertFalse($status['keyboard_mouse_activity']);
        $this->assertFalse($status['screenshots']);
        $this->assertFalse($status['geolocation']);
    }

    public function test_get_data_collection_status_returns_correct_status_for_full_tracking(): void
    {
        // Arrange
        $user = User::factory()->create();
        UserPrivacySetting::factory()
            ->forUser($user)
            ->withFullTracking()
            ->create();

        // Act
        $status = $this->privacyService->getDataCollectionStatus($user->getKey());

        // Assert
        $this->assertTrue($status['manual_time_entries']);
        $this->assertTrue($status['idle_detection']);
        $this->assertTrue($status['app_names']);
        $this->assertTrue($status['urls']);
        $this->assertTrue($status['keyboard_mouse_activity']);
        $this->assertTrue($status['screenshots']);
        $this->assertFalse($status['geolocation']); // Geolocation is separate
    }

    public function test_get_data_collection_status_respects_individual_feature_flags(): void
    {
        // Arrange
        $user = User::factory()->create();
        UserPrivacySetting::factory()
            ->forUser($user)
            ->create([
                'tracking_level' => ActivityTrackingLevel::Monitoring,
                'app_tracking_enabled' => true,
                'url_tracking_enabled' => false, // Disabled despite level
                'geolocation_enabled' => true,
            ]);

        // Act
        $status = $this->privacyService->getDataCollectionStatus($user->getKey());

        // Assert
        $this->assertTrue($status['app_names']); // Enabled
        $this->assertFalse($status['urls']); // Disabled
        $this->assertTrue($status['geolocation']); // Enabled independently
    }
}
