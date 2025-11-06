<?php

namespace Database\Factories;

use App\Models\Webhook;
use App\Models\User;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class WebhookFactory extends Factory
{
    protected $model = Webhook::class;

    public function definition(): array
    {
        return [
            'id' => Str::uuid(),
            'user_id' => User::factory(),
            'organization_id' => Organization::factory(),
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->optional()->sentence(),
            'url' => $this->faker->url(),
            'secret' => Webhook::generateSecret(),
            'events' => $this->faker->randomElements(
                ['time_entry.started', 'time_entry.stopped', 'project.created', 'task.completed'],
                $this->faker->numberBetween(1, 3)
            ),
            'is_active' => true,
            'failure_count' => 0,
            'verification_status' => $this->faker->randomElement(['pending', 'verified']),
            'filters' => null,
            'last_triggered_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function disabled(): self
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function failing(): self
    {
        return $this->state(fn (array $attributes) => [
            'failure_count' => $this->faker->numberBetween(5, 9),
        ]);
    }

    public function verified(): self
    {
        return $this->state(fn (array $attributes) => [
            'verification_status' => 'verified',
        ]);
    }
}
