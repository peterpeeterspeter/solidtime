<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AppActivity;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Crypt;

/**
 * @extends Factory<AppActivity>
 */
class AppActivityFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<AppActivity>
     */
    protected $model = AppActivity::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $apps = [
            'Visual Studio Code',
            'Google Chrome',
            'Slack',
            'Terminal',
            'Notion',
            'Figma',
            'Discord',
            'PhpStorm',
            'Postman',
            'Firefox',
        ];

        $windows = [
            'index.php - VS Code',
            'GitHub - Google Chrome',
            '#general - Slack',
            'bash - Terminal',
            'Project Documentation - Notion',
            'Design System - Figma',
            '#random - Discord',
            'UserController.php - PhpStorm',
            'API Testing - Postman',
            'Laravel Docs - Firefox',
        ];

        $appName = $this->faker->randomElement($apps);
        $windowTitle = $this->faker->randomElement($windows);

        // Encrypt window title
        $encryptedData = Crypt::encryptString(json_encode([
            'window_title' => $windowTitle,
            'url' => $this->faker->optional(0.3)->url(),
        ]));

        $activeSeconds = $this->faker->numberBetween(0, 10);
        $idleSeconds = 10 - $activeSeconds;

        return [
            'user_id' => User::factory(),
            'organization_id' => Organization::factory(),
            'app_name' => $appName,
            'encrypted_data' => $encryptedData,
            'active_seconds' => $activeSeconds,
            'idle_seconds' => $idleSeconds,
            'keyboard_count' => $activeSeconds > 0 ? $this->faker->numberBetween(0, 50) : 0,
            'mouse_count' => $activeSeconds > 0 ? $this->faker->numberBetween(0, 100) : 0,
            'recorded_at' => now(),
            'client_version' => '1.0.0',
            'platform' => $this->faker->randomElement(['windows', 'macos', 'linux']),
        ];
    }

    /**
     * Create a focused work session (high activity, low idle)
     */
    public function focused(): static
    {
        return $this->state(fn (array $attributes) => [
            'active_seconds' => $this->faker->numberBetween(8, 10),
            'idle_seconds' => $this->faker->numberBetween(0, 2),
            'keyboard_count' => $this->faker->numberBetween(30, 50),
            'mouse_count' => $this->faker->numberBetween(50, 100),
        ]);
    }

    /**
     * Create an idle session (high idle, low activity)
     */
    public function idle(): static
    {
        return $this->state(fn (array $attributes) => [
            'active_seconds' => $this->faker->numberBetween(0, 2),
            'idle_seconds' => $this->faker->numberBetween(8, 10),
            'keyboard_count' => 0,
            'mouse_count' => 0,
        ]);
    }

    /**
     * Create activities for a specific app
     */
    public function forApp(string $appName): static
    {
        return $this->state(fn (array $attributes) => [
            'app_name' => $appName,
        ]);
    }

    /**
     * Create activities at a specific time
     */
    public function at(\DateTimeInterface $time): static
    {
        return $this->state(fn (array $attributes) => [
            'recorded_at' => $time,
        ]);
    }

    /**
     * Create activities for a specific user and organization
     */
    public function forUserInOrganization(User $user, Organization $organization): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
            'organization_id' => $organization->id,
        ]);
    }
}
