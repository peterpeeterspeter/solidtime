<?php

declare(strict_types=1);

namespace App\Enums;

enum ActivityTrackingLevel: int
{
    case Manual = 0;
    case IdleDetection = 1;
    case Monitoring = 2;
    case FullTracking = 3;

    public function label(): string
    {
        return match ($this) {
            self::Manual => 'Manual Tracking',
            self::IdleDetection => 'Idle Detection',
            self::Monitoring => 'Activity Monitoring',
            self::FullTracking => 'Full Tracking',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Manual => 'You control the timer. No automatic tracking.',
            self::IdleDetection => 'Detects when you\'re active or idle.',
            self::Monitoring => 'Tracks apps and URLs (encrypted). No screenshots.',
            self::FullTracking => 'Includes screenshots and detailed activity logs.',
        };
    }
}
