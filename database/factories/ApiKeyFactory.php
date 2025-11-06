<?php

namespace Database\Factories;

use App\Models\ApiKey;
use App\Models\User;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ApiKeyFactory extends Factory
{
    protected $model = ApiKey::class;

    public function definition(): array
    {
        $keyData = ApiKey::generateKey();

        return [
            'id' => Str::uuid(),
            'user_id' => User::factory(),
            'organization_id' => Organization::factory(),
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'key_prefix' => $keyData['prefix'],
            'key_hash' => $keyData['hash'],
            'scopes' => $this->faker->randomElements(
                ['time_entries:read', 'time_entries:write', 'projects:read', 'projects:write'],
                $this->faker->numberBetween(1, 4)
            ),
            'is_active' => true,
            'last_used_at' => null,
            'last_used_ip' => null,
            'usage_count' => 0,
            'expires_at' => $this->faker->optional(0.3)->dateTimeBetween('+1 month', '+1 year'),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function expired(): self
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subDays(1),
        ]);
    }

    public function revoked(): self
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function fullAccess(): self
    {
        return $this->state(fn (array $attributes) => [
            'scopes' => ['*'],
        ]);
    }
}
