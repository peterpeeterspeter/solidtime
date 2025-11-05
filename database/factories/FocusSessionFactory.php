<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\FocusSession;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FocusSession>
 */
class FocusSessionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<FocusSession>
     */
    protected $model = FocusSession::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $apps = [
            'Visual Studio Code',
            'PhpStorm',
            'IntelliJ IDEA',
            'Sublime Text',
            'Atom',
            'Figma',
            'Adobe XD',
            'Notion',
            'Google Docs',
            'Terminal',
        ];

        $durationMinutes = $this->faker->numberBetween(20, 120); // 20 min to 2 hours
        $startTime = now()->subDays($this->faker->numberBetween(0, 30))->setHour($this->faker->numberBetween(8, 17))->setMinute(0);
        $endTime = (clone $startTime)->addMinutes($durationMinutes);

        $numApps = $this->faker->numberBetween(1, 3);
        $appsUsed = $this->faker->randomElements($apps, $numApps);
        $primaryApp = $appsUsed[0];
        $appSwitches = $numApps - 1;

        // Calculate focus score
        $durationScore = min(100, ($durationMinutes / 120) * 50);
        $switchScore = max(0, 100 - ($appSwitches * 5));
        $appScore = max(0, 100 - ($numApps * 10));
        $focusScore = (int) round(($durationScore * 0.5) + ($switchScore * 0.3) + ($appScore * 0.2));

        return [
            'user_id' => User::factory(),
            'organization_id' => Organization::factory(),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'duration_minutes' => $durationMinutes,
            'app_switches' => $appSwitches,
            'unique_apps_count' => $numApps,
            'interruptions_count' => $appSwitches,
            'focus_score' => $focusScore,
            'apps_used' => $appsUsed,
            'primary_app' => $primaryApp,
        ];
    }

    /**
     * Create a high-quality focus session
     */
    public function excellent(): static
    {
        return $this->state(function (array $attributes) {
            $durationMinutes = $this->faker->numberBetween(60, 120); // 1-2 hours
            $startTime = $attributes['start_time'];
            $endTime = (clone $startTime)->addMinutes($durationMinutes);
            $primaryApp = 'Visual Studio Code';

            return [
                'duration_minutes' => $durationMinutes,
                'end_time' => $endTime,
                'app_switches' => 0,
                'unique_apps_count' => 1,
                'interruptions_count' => 0,
                'focus_score' => $this->faker->numberBetween(85, 100),
                'apps_used' => [$primaryApp],
                'primary_app' => $primaryApp,
            ];
        });
    }

    /**
     * Create a low-quality focus session
     */
    public function poor(): static
    {
        return $this->state(function (array $attributes) {
            $durationMinutes = $this->faker->numberBetween(20, 30); // Short duration
            $startTime = $attributes['start_time'];
            $endTime = (clone $startTime)->addMinutes($durationMinutes);
            $appsUsed = ['Google Chrome', 'Slack', 'Discord', 'Twitter'];
            $appSwitches = 10;

            return [
                'duration_minutes' => $durationMinutes,
                'end_time' => $endTime,
                'app_switches' => $appSwitches,
                'unique_apps_count' => count($appsUsed),
                'interruptions_count' => $appSwitches,
                'focus_score' => $this->faker->numberBetween(10, 40),
                'apps_used' => $appsUsed,
                'primary_app' => 'Google Chrome',
            ];
        });
    }

    /**
     * Create a deep work session (40+ min, high score)
     */
    public function deepWork(): static
    {
        return $this->state(function (array $attributes) {
            $durationMinutes = $this->faker->numberBetween(60, 180); // 1-3 hours
            $startTime = $attributes['start_time'];
            $endTime = (clone $startTime)->addMinutes($durationMinutes);
            $primaryApp = $this->faker->randomElement([
                'Visual Studio Code',
                'PhpStorm',
                'IntelliJ IDEA',
                'Figma',
            ]);

            return [
                'duration_minutes' => $durationMinutes,
                'end_time' => $endTime,
                'app_switches' => $this->faker->numberBetween(0, 2),
                'unique_apps_count' => $this->faker->numberBetween(1, 2),
                'interruptions_count' => $this->faker->numberBetween(0, 2),
                'focus_score' => $this->faker->numberBetween(70, 100),
                'apps_used' => [$primaryApp],
                'primary_app' => $primaryApp,
            ];
        });
    }

    /**
     * Create a focus session at a specific time
     */
    public function at(\DateTimeInterface $time): static
    {
        return $this->state(function (array $attributes) use ($time) {
            $durationMinutes = $attributes['duration_minutes'];
            $startTime = \Carbon\Carbon::parse($time);
            $endTime = (clone $startTime)->addMinutes($durationMinutes);

            return [
                'start_time' => $startTime,
                'end_time' => $endTime,
            ];
        });
    }

    /**
     * Create a focus session for a specific user and organization
     */
    public function forUserInOrganization(User $user, Organization $organization): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
            'organization_id' => $organization->id,
        ]);
    }
}
