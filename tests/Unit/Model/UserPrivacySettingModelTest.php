<?php

declare(strict_types=1);

namespace Tests\Unit\Model;

use App\Enums\ActivityTrackingLevel;
use App\Models\User;
use App\Models\UserPrivacySetting;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(UserPrivacySetting::class)]
class UserPrivacySettingModelTest extends ModelTestAbstract
{
    public function test_user_privacy_setting_belongs_to_user(): void
    {
        // Arrange
        $user = User::factory()->create();
        $setting = UserPrivacySetting::factory()->forUser($user)->create();

        // Act
        $relatedUser = $setting->user;

        // Assert
        $this->assertInstanceOf(User::class, $relatedUser);
        $this->assertSame($user->getKey(), $relatedUser->getKey());
    }

    public function test_user_privacy_setting_has_default_values(): void
    {
        // Arrange & Act
        $setting = UserPrivacySetting::factory()->create();

        // Assert
        $this->assertSame(ActivityTrackingLevel::Manual, $setting->tracking_level);
        $this->assertFalse($setting->screenshot_enabled);
        $this->assertFalse($setting->app_tracking_enabled);
        $this->assertFalse($setting->url_tracking_enabled);
        $this->assertFalse($setting->keyboard_mouse_tracking_enabled);
        $this->assertFalse($setting->geolocation_enabled);
        $this->assertSame(90, $setting->data_retention_days);
        $this->assertTrue($setting->encryption_enabled);
    }

    public function test_user_privacy_setting_can_be_created_with_idle_detection(): void
    {
        // Arrange & Act
        $setting = UserPrivacySetting::factory()->withIdleDetection()->create();

        // Assert
        $this->assertSame(ActivityTrackingLevel::IdleDetection, $setting->tracking_level);
    }

    public function test_user_privacy_setting_can_be_created_with_monitoring(): void
    {
        // Arrange & Act
        $setting = UserPrivacySetting::factory()->withMonitoring()->create();

        // Assert
        $this->assertSame(ActivityTrackingLevel::Monitoring, $setting->tracking_level);
        $this->assertTrue($setting->app_tracking_enabled);
        $this->assertTrue($setting->url_tracking_enabled);
    }

    public function test_user_privacy_setting_can_be_created_with_full_tracking(): void
    {
        // Arrange & Act
        $setting = UserPrivacySetting::factory()->withFullTracking()->create();

        // Assert
        $this->assertSame(ActivityTrackingLevel::FullTracking, $setting->tracking_level);
        $this->assertTrue($setting->screenshot_enabled);
        $this->assertTrue($setting->app_tracking_enabled);
        $this->assertTrue($setting->url_tracking_enabled);
        $this->assertTrue($setting->keyboard_mouse_tracking_enabled);
    }

    public function test_user_privacy_setting_tracking_level_is_cast_to_enum(): void
    {
        // Arrange & Act
        $setting = UserPrivacySetting::factory()->withTrackingLevel(ActivityTrackingLevel::Monitoring)->create();

        // Assert
        $this->assertInstanceOf(ActivityTrackingLevel::class, $setting->tracking_level);
        $this->assertSame(ActivityTrackingLevel::Monitoring, $setting->tracking_level);
    }

    public function test_user_privacy_setting_can_update_data_retention(): void
    {
        // Arrange
        $setting = UserPrivacySetting::factory()->create();

        // Act
        $setting->data_retention_days = 30;
        $setting->save();

        // Assert
        $this->assertSame(30, $setting->fresh()->data_retention_days);
    }

    public function test_user_privacy_setting_is_deleted_when_user_is_deleted(): void
    {
        // Arrange
        $user = User::factory()->create();
        $setting = UserPrivacySetting::factory()->forUser($user)->create();

        // Act
        $user->delete();

        // Assert
        $this->assertDatabaseMissing('user_privacy_settings', [
            'id' => $setting->getKey(),
        ]);
    }

    public function test_user_can_only_have_one_privacy_setting(): void
    {
        // Arrange
        $user = User::factory()->create();
        UserPrivacySetting::factory()->forUser($user)->create();

        // Assert - trying to create another setting for the same user should fail
        $this->expectException(\Illuminate\Database\QueryException::class);

        // Act
        UserPrivacySetting::factory()->forUser($user)->create();
    }
}
