<?php

declare(strict_types=1);

namespace App\Http\Resources\V1\UserPrivacySetting;

use App\Http\Resources\V1\BaseResource;
use App\Models\PrivacyConsentLog;
use Illuminate\Http\Request;

/**
 * @property PrivacyConsentLog $resource
 */
class PrivacyConsentLogResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, string|null>
     */
    public function toArray(Request $request): array
    {
        return [
            /** @var string $id ID */
            'id' => $this->resource->id,
            /** @var string $user_id User ID */
            'user_id' => $this->resource->user_id,
            /** @var string $setting_changed The privacy setting that was changed */
            'setting_changed' => $this->resource->setting_changed,
            /** @var string|null $old_value Previous value of the setting */
            'old_value' => $this->resource->old_value,
            /** @var string $new_value New value of the setting */
            'new_value' => $this->resource->new_value,
            /** @var string|null $reason User's optional explanation for the change */
            'reason' => $this->resource->reason,
            /** @var string $consented_at When the user consented to the change */
            'consented_at' => $this->formatDateTime($this->resource->consented_at),
            /** @var string|null $ip_address IP address of the user */
            'ip_address' => $this->resource->ip_address,
            /** @var string|null $user_agent User agent string */
            'user_agent' => $this->resource->user_agent,
            /** @var string $created_at When the log entry was created */
            'created_at' => $this->formatDateTime($this->resource->created_at),
        ];
    }
}
