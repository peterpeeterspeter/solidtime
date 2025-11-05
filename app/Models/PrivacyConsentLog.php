<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $user_id
 * @property string $setting_changed
 * @property string|null $old_value
 * @property string $new_value
 * @property string|null $reason
 * @property Carbon $consented_at
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property Carbon $created_at
 * @property User $user
 *
 * @method BelongsTo<User, $this> user()
 */
class PrivacyConsentLog extends Model
{
    use HasUuids;

    /**
     * Indicates if the model should be timestamped.
     * Only created_at is used, no updated_at.
     */
    public const UPDATED_AT = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'setting_changed',
        'old_value',
        'new_value',
        'reason',
        'consented_at',
        'ip_address',
        'user_agent',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'consented_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    /**
     * Get the user that this consent log belongs to.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
