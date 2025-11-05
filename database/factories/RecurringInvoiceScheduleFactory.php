<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Client;
use App\Models\Organization;
use App\Models\Project;
use App\Models\RecurringInvoiceSchedule;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RecurringInvoiceSchedule>
 */
class RecurringInvoiceScheduleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = $this->faker->numberBetween(1000, 10000);
        $taxRate = $this->faker->numberBetween(0, 20);
        $taxAmount = round($subtotal * $taxRate / 100, 2);
        $discountAmount = 0;
        $total = $subtotal + $taxAmount - $discountAmount;

        return [
            'user_id' => User::factory(),
            'organization_id' => Organization::factory(),
            'client_id' => Client::factory(),
            'project_id' => null,
            'name' => $this->faker->words(3, true) . ' - ' . $this->faker->company(),
            'frequency' => $this->faker->randomElement(['daily', 'weekly', 'biweekly', 'monthly', 'quarterly', 'biannually', 'annually']),
            'interval' => 1,
            'day_of_month' => null,
            'day_of_week' => null,
            'start_date' => now(),
            'end_date' => null,
            'next_generation_date' => now()->addMonth(),
            'last_generated_date' => null,
            'max_occurrences' => null,
            'occurrences_count' => 0,
            'status' => 'active',
            'from_details' => [
                'name' => $this->faker->company(),
                'email' => $this->faker->companyEmail(),
                'address' => $this->faker->address(),
            ],
            'to_details' => [
                'name' => $this->faker->company(),
                'email' => $this->faker->companyEmail(),
                'address' => $this->faker->address(),
            ],
            'line_items' => [
                [
                    'description' => $this->faker->sentence(),
                    'quantity' => 1,
                    'unit_price' => $subtotal,
                    'amount' => $subtotal,
                ],
            ],
            'notes' => $this->faker->optional()->paragraph(),
            'terms' => $this->faker->optional()->paragraph(),
            'subtotal' => $subtotal,
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount,
            'discount_amount' => $discountAmount,
            'total' => $total,
            'currency' => 'USD',
            'due_days' => 30,
            'due_date_type' => 'days_after_generation',
            'auto_send' => false,
            'auto_charge' => false,
            'include_time_entries' => false,
            'time_entries_from_date' => null,
            'time_entries_to_date' => null,
            'notify_on_generation' => false,
            'notification_emails' => [],
            'metadata' => null,
        ];
    }

    public function forUser(User $user): self
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->getKey(),
        ]);
    }

    public function forOrganization(Organization $organization): self
    {
        return $this->state(fn (array $attributes) => [
            'organization_id' => $organization->getKey(),
        ]);
    }

    public function forClient(Client $client): self
    {
        return $this->state(fn (array $attributes) => [
            'client_id' => $client->getKey(),
        ]);
    }

    public function forProject(Project $project): self
    {
        return $this->state(fn (array $attributes) => [
            'project_id' => $project->getKey(),
        ]);
    }

    public function daily(): self
    {
        return $this->state(fn (array $attributes) => [
            'frequency' => 'daily',
            'interval' => 1,
            'next_generation_date' => now()->addDay(),
        ]);
    }

    public function weekly(): self
    {
        return $this->state(fn (array $attributes) => [
            'frequency' => 'weekly',
            'interval' => 1,
            'day_of_week' => 1, // Monday
            'next_generation_date' => now()->addWeek(),
        ]);
    }

    public function monthly(): self
    {
        return $this->state(fn (array $attributes) => [
            'frequency' => 'monthly',
            'interval' => 1,
            'day_of_month' => 1,
            'next_generation_date' => now()->addMonth(),
        ]);
    }

    public function active(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    public function paused(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'paused',
        ]);
    }

    public function completed(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'end_date' => now()->subDay(),
        ]);
    }

    public function autoSend(): self
    {
        return $this->state(fn (array $attributes) => [
            'auto_send' => true,
        ]);
    }

    public function autoCharge(): self
    {
        return $this->state(fn (array $attributes) => [
            'auto_charge' => true,
        ]);
    }

    public function dueForGeneration(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'next_generation_date' => now()->subDay(),
        ]);
    }

    public function randomCreatedAt(): self
    {
        return $this->state(function (array $attributes): array {
            return [
                'created_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            ];
        });
    }
}
