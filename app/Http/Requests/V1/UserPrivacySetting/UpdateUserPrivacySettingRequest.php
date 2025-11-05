<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\UserPrivacySetting;

use App\Enums\ActivityTrackingLevel;
use App\Http\Requests\V1\BaseFormRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class UpdateUserPrivacySettingRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<string|ValidationRule>>
     */
    public function rules(): array
    {
        return [
            'tracking_level' => [
                'sometimes',
                'integer',
                Rule::in([0, 1, 2, 3]),
            ],
            'screenshot_enabled' => [
                'sometimes',
                'boolean',
            ],
            'app_tracking_enabled' => [
                'sometimes',
                'boolean',
            ],
            'url_tracking_enabled' => [
                'sometimes',
                'boolean',
            ],
            'keyboard_mouse_tracking_enabled' => [
                'sometimes',
                'boolean',
            ],
            'geolocation_enabled' => [
                'sometimes',
                'boolean',
            ],
            'data_retention_days' => [
                'sometimes',
                'integer',
                'min:1',
                'max:3650', // Max 10 years
            ],
            'encryption_enabled' => [
                'sometimes',
                'boolean',
            ],
            'reason' => [
                'sometimes',
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tracking_level.in' => 'The tracking level must be between 0 (Manual) and 3 (Full Tracking).',
            'data_retention_days.min' => 'Data retention must be at least 1 day.',
            'data_retention_days.max' => 'Data retention cannot exceed 10 years.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert tracking_level to integer if provided as enum
        if ($this->has('tracking_level') && $this->tracking_level instanceof ActivityTrackingLevel) {
            $this->merge([
                'tracking_level' => $this->tracking_level->value,
            ]);
        }
    }
}
