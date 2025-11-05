<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\V1\UserPrivacySetting\UpdateUserPrivacySettingRequest;
use App\Http\Resources\V1\UserPrivacySetting\PrivacyConsentLogResource;
use App\Http\Resources\V1\UserPrivacySetting\UserPrivacySettingResource;
use App\Service\PrivacyService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserPrivacySettingController extends Controller
{
    public function __construct(
        private readonly PrivacyService $privacyService,
    ) {
        parent::__construct(app(\App\Service\PermissionStore::class));
    }

    /**
     * Get the current user's privacy settings
     *
     * Returns the privacy settings for the authenticated user, including
     * tracking level, feature flags, and data retention preferences.
     * If no settings exist, default privacy-first settings are returned.
     *
     * @operationId getUserPrivacySettings
     *
     * @throws AuthorizationException
     */
    public function show(): UserPrivacySettingResource
    {
        $user = $this->user();

        $settings = $this->privacyService->getOrCreateSettings($user->id);

        return new UserPrivacySettingResource($settings);
    }

    /**
     * Update the current user's privacy settings
     *
     * Updates privacy settings for the authenticated user and logs the
     * consent for GDPR compliance. All setting changes are tracked in
     * the privacy consent log with IP address, user agent, and timestamp.
     *
     * @operationId updateUserPrivacySettings
     *
     * @throws AuthorizationException
     */
    public function update(UpdateUserPrivacySettingRequest $request): UserPrivacySettingResource
    {
        $user = $this->user();

        $validated = $request->validated();
        $reason = $validated['reason'] ?? null;
        unset($validated['reason']);

        $settings = $this->privacyService->updateSettings(
            $user->id,
            $validated,
            $reason
        );

        return new UserPrivacySettingResource($settings);
    }

    /**
     * Get the current user's privacy consent history
     *
     * Returns the most recent privacy setting changes for the authenticated
     * user, showing what was changed, when, and from what device/IP address.
     * This provides full transparency and GDPR compliance for audit purposes.
     *
     * @operationId getUserPrivacyConsentHistory
     *
     * @throws AuthorizationException
     */
    public function consentHistory(): AnonymousResourceCollection
    {
        $user = $this->user();

        $history = $this->privacyService->getConsentHistory($user->id, 50);

        return PrivacyConsentLogResource::collection($history);
    }

    /**
     * Get data collection status for the current user
     *
     * Returns a summary of what data is currently being collected based
     * on the user's privacy settings. This provides a clear overview for
     * the Privacy Control Center dashboard.
     *
     * @operationId getUserDataCollectionStatus
     *
     * @return array<string, bool>
     *
     * @throws AuthorizationException
     */
    public function dataCollectionStatus(): array
    {
        $user = $this->user();

        $status = $this->privacyService->getDataCollectionStatus($user->id);

        return [
            'data' => $status,
        ];
    }
}
