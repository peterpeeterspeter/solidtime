<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class PaymentGatewayConnection extends Model
{
    use HasUuids;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'gateway',
        'gateway_account_id',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'is_active',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'token_expires_at' => 'datetime',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'access_token',
        'refresh_token',
    ];

    /**
     * Get the user that owns the connection.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the decrypted access token.
     */
    public function getDecryptedAccessToken(): ?string
    {
        return $this->access_token ? Crypt::decryptString($this->access_token) : null;
    }

    /**
     * Get the decrypted refresh token.
     */
    public function getDecryptedRefreshToken(): ?string
    {
        return $this->refresh_token ? Crypt::decryptString($this->refresh_token) : null;
    }

    /**
     * Set the encrypted access token.
     */
    public function setAccessToken(?string $token): void
    {
        $this->access_token = $token ? Crypt::encryptString($token) : null;
    }

    /**
     * Set the encrypted refresh token.
     */
    public function setRefreshToken(?string $token): void
    {
        $this->refresh_token = $token ? Crypt::encryptString($token) : null;
    }

    /**
     * Check if the access token is expired.
     */
    public function isTokenExpired(): bool
    {
        if (!$this->token_expires_at) {
            return false;
        }

        return $this->token_expires_at->isPast();
    }

    /**
     * Scope to get active connections.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get connections by gateway.
     */
    public function scopeByGateway($query, string $gateway)
    {
        return $query->where('gateway', $gateway);
    }
}
