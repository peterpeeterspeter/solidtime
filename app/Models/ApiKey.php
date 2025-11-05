<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ApiKeyFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @property string $id
 * @property string $user_id
 * @property string $organization_id
 * @property string $name
 * @property string $key_prefix
 * @property string $key_hash
 * @property string|null $description
 * @property array $scopes
 * @property bool $is_active
 * @property Carbon|null $last_used_at
 * @property string|null $last_used_ip
 * @property int $usage_count
 * @property Carbon|null $expires_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read User $user
 * @property-read Organization $organization
 */
class ApiKey extends Model
{
    /** @use HasFactory<ApiKeyFactory> */
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'organization_id',
        'name',
        'key_prefix',
        'key_hash',
        'description',
        'scopes',
        'is_active',
        'last_used_at',
        'last_used_ip',
        'usage_count',
        'expires_at',
    ];

    protected $casts = [
        'scopes' => 'array',
        'is_active' => 'boolean',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
        'usage_count' => 'integer',
    ];

    protected $hidden = [
        'key_hash',
    ];

    /**
     * Generate a new API key with prefix and hash
     *
     * @return array{key: string, prefix: string, hash: string}
     */
    public static function generateKey(): array
    {
        $key = 'sk_' . Str::random(48); // Total: 51 chars (sk_ + 48 random)
        $prefix = substr($key, 0, 11); // sk_ + first 8 chars
        $hash = Hash::make($key);

        return [
            'key' => $key,
            'prefix' => $prefix,
            'hash' => $hash,
        ];
    }

    /**
     * Verify a plain text key against this API key's hash
     */
    public function verifyKey(string $plainKey): bool
    {
        return Hash::check($plainKey, $this->key_hash);
    }

    /**
     * Check if the API key is valid (active and not expired)
     */
    public function isValid(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Check if the API key has a specific scope
     */
    public function hasScope(string $scope): bool
    {
        return in_array($scope, $this->scopes, true) || in_array('*', $this->scopes, true);
    }

    /**
     * Record usage of this API key
     */
    public function recordUsage(?string $ipAddress = null): void
    {
        $this->update([
            'last_used_at' => now(),
            'last_used_ip' => $ipAddress,
            'usage_count' => $this->usage_count + 1,
        ]);
    }

    /**
     * Revoke (deactivate) this API key
     */
    public function revoke(): void
    {
        $this->update(['is_active' => false]);
    }

    /**
     * Get the user that owns this API key
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the organization that owns this API key
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Scope: Active API keys only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Scope: Expired API keys only
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }

    /**
     * Scope: API keys for a specific organization
     */
    public function scopeForOrganization($query, string $organizationId)
    {
        return $query->where('organization_id', $organizationId);
    }

    /**
     * Scope: API keys for a specific user
     */
    public function scopeForUser($query, string $userId)
    {
        return $query->where('user_id', $userId);
    }
}
