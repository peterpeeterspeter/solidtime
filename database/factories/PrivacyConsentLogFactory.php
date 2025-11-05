<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\PrivacyConsentLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PrivacyConsentLog>
 */
class PrivacyConsentLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'setting_changed' => 'tracking_level',
            'old_value' => '0',
            'new_value' => '1',
            'reason' => $this->faker->optional(0.3)->sentence(),
            'consented_at' => now(),
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
        ];
    }

    public function forUser(User $user): static
    {
        return $this->state(function (array $attributes) use ($user): array {
            return [
                'user_id' => $user->getKey(),
            ];
        });
    }

    public function forSetting(string $setting, ?string $oldValue, string $newValue): static
    {
        return $this->state(function (array $attributes) use ($setting, $oldValue, $newValue): array {
            return [
                'setting_changed' => $setting,
                'old_value' => $oldValue,
                'new_value' => $newValue,
            ];
        });
    }

    public function withReason(string $reason): static
    {
        return $this->state(function (array $attributes) use ($reason): array {
            return [
                'reason' => $reason,
            ];
        });
    }

    public function withoutReason(): static
    {
        return $this->state(function (array $attributes): array {
            return [
                'reason' => null,
            ];
        });
    }

    public function consentedAt(\DateTimeInterface $dateTime): static
    {
        return $this->state(function (array $attributes) use ($dateTime): array {
            return [
                'consented_at' => $dateTime,
            ];
        });
    }
}
