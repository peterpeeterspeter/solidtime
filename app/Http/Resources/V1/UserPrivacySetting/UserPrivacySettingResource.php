<?php

declare(strict_types=1);

namespace App\Http\Resources\V1\UserPrivacySetting;

use App\Http\Resources\V1\BaseResource;
use App\Models\UserPrivacySetting;
use Illuminate\Http\Request;

/**
 * @property UserPrivacySetting $resource
 */
class UserPrivacySettingResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, string|bool|int|null>
     */
    public function toArray(Request $request): array
    {
        return [
            /** @var string $id ID */
            'id' => $this->resource->id,
            /** @var string $user_id User ID */
            'user_id' => $this->resource->user_id,
            /** @var int $tracking_level Tracking level (0: Manual, 1: Idle Detection, 2: Monitoring, 3: Full Tracking) */
            'tracking_level' => $this->resource->tracking_level->value,
            /** @var string $tracking_level_label Human-readable tracking level */
            'tracking_level_label' => $this->resource->tracking_level->label(),
            /** @var string $tracking_level_description Description of tracking level */
            'tracking_level_description' => $this->resource->tracking_level->description(),
            /** @var bool $screenshot_enabled Whether screenshot capture is enabled */
            'screenshot_enabled' => $this->resource->screenshot_enabled,
            /** @var bool $app_tracking_enabled Whether application tracking is enabled */
            'app_tracking_enabled' => $this->resource->app_tracking_enabled,
            /** @var bool $url_tracking_enabled Whether URL tracking is enabled */
            'url_tracking_enabled' => $this->resource->url_tracking_enabled,
            /** @var bool $keyboard_mouse_tracking_enabled Whether keyboard/mouse activity tracking is enabled */
            'keyboard_mouse_tracking_enabled' => $this->resource->keyboard_mouse_tracking_enabled,
            /** @var bool $geolocation_enabled Whether geolocation tracking is enabled */
            'geolocation_enabled' => $this->resource->geolocation_enabled,
            /** @var int $data_retention_days Number of days to retain activity data */
            'data_retention_days' => $this->resource->data_retention_days,
            /** @var bool $encryption_enabled Whether encryption is enabled */
            'encryption_enabled' => $this->resource->encryption_enabled,
            /** @var string $created_at When the privacy settings were created */
            'created_at' => $this->formatDateTime($this->resource->created_at),
            /** @var string $updated_at When the privacy settings were last updated */
            'updated_at' => $this->formatDateTime($this->resource->updated_at),
        ];
    }
}
