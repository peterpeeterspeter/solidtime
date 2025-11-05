<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ActivityTrackingLevel;
use App\Models\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $user_id
 * @property ActivityTrackingLevel $tracking_level
 * @property bool $screenshot_enabled
 * @property bool $app_tracking_enabled
 * @property bool $url_tracking_enabled
 * @property bool $keyboard_mouse_tracking_enabled
 * @property bool $geolocation_enabled
 * @property int $data_retention_days
 * @property bool $encryption_enabled
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property User $user
 *
 * @method BelongsTo<User, $this> user()
 */
class UserPrivacySetting extends Model
{
    use HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'tracking_level',
        'screenshot_enabled',
        'app_tracking_enabled',
        'url_tracking_enabled',
        'keyboard_mouse_tracking_enabled',
        'geolocation_enabled',
        'data_retention_days',
        'encryption_enabled',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'tracking_level' => ActivityTrackingLevel::class,
        'screenshot_enabled' => 'boolean',
        'app_tracking_enabled' => 'boolean',
        'url_tracking_enabled' => 'boolean',
        'keyboard_mouse_tracking_enabled' => 'boolean',
        'geolocation_enabled' => 'boolean',
        'data_retention_days' => 'integer',
        'encryption_enabled' => 'boolean',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'tracking_level' => ActivityTrackingLevel::Manual,
        'screenshot_enabled' => false,
        'app_tracking_enabled' => false,
        'url_tracking_enabled' => false,
        'keyboard_mouse_tracking_enabled' => false,
        'geolocation_enabled' => false,
        'data_retention_days' => 90,
        'encryption_enabled' => true,
    ];

    /**
     * Get the user that owns the privacy settings.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
