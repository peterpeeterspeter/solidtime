<?php

declare(strict_types=1);

namespace App\Service;

use App\Enums\ActivityTrackingLevel;
use App\Models\PrivacyConsentLog;
use App\Models\UserPrivacySetting;
use Illuminate\Support\Collection;

class PrivacyService
{
    /**
     * Get or create default privacy settings for a user.
     */
    public function getOrCreateSettings(string $userId): UserPrivacySetting
    {
        return UserPrivacySetting::firstOrCreate(
            ['user_id' => $userId],
            [
                'tracking_level' => ActivityTrackingLevel::Manual,
                'screenshot_enabled' => false,
                'app_tracking_enabled' => false,
                'url_tracking_enabled' => false,
                'keyboard_mouse_tracking_enabled' => false,
                'geolocation_enabled' => false,
                'data_retention_days' => 90,
                'encryption_enabled' => true,
            ]
        );
    }

    /**
     * Update privacy settings for a user with consent logging.
     *
     * @param  array<string, mixed>  $newSettings
     */
    public function updateSettings(string $userId, array $newSettings, ?string $reason = null): UserPrivacySetting
    {
        $settings = $this->getOrCreateSettings($userId);
        $oldValues = $settings->toArray();

        // Log each changed setting
        foreach ($newSettings as $key => $value) {
            if (array_key_exists($key, $oldValues) && $oldValues[$key] !== $value) {
                $this->logConsent(
                    $userId,
                    $key,
                    $this->valueToString($oldValues[$key]),
                    $this->valueToString($value),
                    $reason
                );
            }
        }

        $settings->update($newSettings);

        return $settings->fresh();
    }

    /**
     * Log a privacy consent change.
     */
    public function logConsent(
        string $userId,
        string $setting,
        ?string $oldValue,
        string $newValue,
        ?string $reason = null
    ): void {
        PrivacyConsentLog::create([
            'user_id' => $userId,
            'setting_changed' => $setting,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'reason' => $reason,
            'consented_at' => now(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Get consent history for a user.
     *
     * @return Collection<int, PrivacyConsentLog>
     */
    public function getConsentHistory(string $userId, int $limit = 50): Collection
    {
        return PrivacyConsentLog::where('user_id', $userId)
            ->orderBy('consented_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Check if a user can enable a specific privacy feature.
     */
    public function canEnableFeature(string $userId, string $feature): bool
    {
        $settings = $this->getOrCreateSettings($userId);

        // Business logic: Can't enable screenshots without monitoring level 2+
        if ($feature === 'screenshot_enabled') {
            return $settings->tracking_level->value >= ActivityTrackingLevel::Monitoring->value;
        }

        // Business logic: Can't enable app/URL tracking without at least idle detection
        if (in_array($feature, ['app_tracking_enabled', 'url_tracking_enabled'])) {
            return $settings->tracking_level->value >= ActivityTrackingLevel::IdleDetection->value;
        }

        return true;
    }

    /**
     * Get a human-readable description of what data is currently being collected.
     *
     * @return array<string, bool>
     */
    public function getDataCollectionStatus(string $userId): array
    {
        $settings = $this->getOrCreateSettings($userId);

        return [
            'manual_time_entries' => true, // Always collected
            'idle_detection' => $settings->tracking_level->value >= ActivityTrackingLevel::IdleDetection->value,
            'app_names' => $settings->app_tracking_enabled && $settings->tracking_level->value >= ActivityTrackingLevel::Monitoring->value,
            'urls' => $settings->url_tracking_enabled && $settings->tracking_level->value >= ActivityTrackingLevel::Monitoring->value,
            'keyboard_mouse_activity' => $settings->keyboard_mouse_tracking_enabled && $settings->tracking_level->value >= ActivityTrackingLevel::Monitoring->value,
            'screenshots' => $settings->screenshot_enabled && $settings->tracking_level->value >= ActivityTrackingLevel::FullTracking->value,
            'geolocation' => $settings->geolocation_enabled,
        ];
    }

    /**
     * Convert a value to a string representation for logging.
     */
    private function valueToString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof ActivityTrackingLevel) {
            return (string) $value->value;
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_array($value)) {
            return json_encode($value);
        }

        return (string) $value;
    }
}
