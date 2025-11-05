<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ApiKey;
use Carbon\Carbon;

class ApiKeyService
{
    /**
     * Create a new API key
     */
    public function create(
        string $userId,
        string $organizationId,
        string $name,
        array $scopes,
        ?string $description = null,
        ?Carbon $expiresAt = null
    ): array {
        $keyData = ApiKey::generateKey();

        $apiKey = ApiKey::create([
            'user_id' => $userId,
            'organization_id' => $organizationId,
            'name' => $name,
            'key_prefix' => $keyData['prefix'],
            'key_hash' => $keyData['hash'],
            'description' => $description,
            'scopes' => $scopes,
            'expires_at' => $expiresAt,
            'is_active' => true,
        ]);

        return [
            'api_key' => $apiKey,
            'plain_key' => $keyData['key'], // Only returned once!
        ];
    }

    /**
     * Authenticate with an API key
     */
    public function authenticate(string $plainKey): ?ApiKey
    {
        $prefix = substr($plainKey, 0, 11);

        $apiKey = ApiKey::where('key_prefix', $prefix)
            ->active()
            ->first();

        if (! $apiKey) {
            return null;
        }

        if (! $apiKey->verifyKey($plainKey)) {
            return null;
        }

        $apiKey->recordUsage(request()->ip());

        return $apiKey;
    }

    /**
     * Revoke an API key
     */
    public function revoke(string $apiKeyId): bool
    {
        $apiKey = ApiKey::find($apiKeyId);

        if (! $apiKey) {
            return false;
        }

        $apiKey->revoke();

        return true;
    }

    /**
     * Get all API keys for an organization
     */
    public function getForOrganization(string $organizationId)
    {
        return ApiKey::forOrganization($organizationId)
            ->with('user')
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Get all API keys for a user
     */
    public function getForUser(string $userId)
    {
        return ApiKey::forUser($userId)
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Update API key scopes
     */
    public function updateScopes(string $apiKeyId, array $scopes): bool
    {
        $apiKey = ApiKey::find($apiKeyId);

        if (! $apiKey) {
            return false;
        }

        $apiKey->update(['scopes' => $scopes]);

        return true;
    }

    /**
     * Clean up expired API keys
     */
    public function cleanupExpired(): int
    {
        return ApiKey::expired()->delete();
    }
}
