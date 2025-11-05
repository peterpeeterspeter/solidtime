<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ActivityTrackingLevel;
use App\Models\User;
use App\Models\UserPrivacySetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserPrivacySetting>
 */
class UserPrivacySettingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'tracking_level' => ActivityTrackingLevel::Manual,
            'screenshot_enabled' => false,
            'app_tracking_enabled' => false,
            'url_tracking_enabled' => false,
            'keyboard_mouse_tracking_enabled' => false,
            'geolocation_enabled' => false,
            'data_retention_days' => 90,
            'encryption_enabled' => true,
        ];
    }

    public function forUser(User $user): static
    {
        return $this->state(function (array $attributes) use ($user): array {
            return [
                'user_id' => $user->getKey(),
            ];
        });
    }

    public function withTrackingLevel(ActivityTrackingLevel $level): static
    {
        return $this->state(function (array $attributes) use ($level): array {
            return [
                'tracking_level' => $level,
            ];
        });
    }

    public function withIdleDetection(): static
    {
        return $this->state(function (array $attributes): array {
            return [
                'tracking_level' => ActivityTrackingLevel::IdleDetection,
            ];
        });
    }

    public function withMonitoring(): static
    {
        return $this->state(function (array $attributes): array {
            return [
                'tracking_level' => ActivityTrackingLevel::Monitoring,
                'app_tracking_enabled' => true,
                'url_tracking_enabled' => true,
            ];
        });
    }

    public function withFullTracking(): static
    {
        return $this->state(function (array $attributes): array {
            return [
                'tracking_level' => ActivityTrackingLevel::FullTracking,
                'screenshot_enabled' => true,
                'app_tracking_enabled' => true,
                'url_tracking_enabled' => true,
                'keyboard_mouse_tracking_enabled' => true,
            ];
        });
    }

    public function withScreenshots(): static
    {
        return $this->state(function (array $attributes): array {
            return [
                'screenshot_enabled' => true,
            ];
        });
    }

    public function withDataRetention(int $days): static
    {
        return $this->state(function (array $attributes) use ($days): array {
            return [
                'data_retention_days' => $days,
            ];
        });
    }
}
