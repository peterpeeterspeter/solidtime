<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = $this->faker->numberBetween(100, 10000);
        $taxRate = $this->faker->numberBetween(0, 20);
        $taxAmount = round($subtotal * $taxRate / 100, 2);
        $discountAmount = $this->faker->numberBetween(0, $subtotal / 10);
        $total = $subtotal + $taxAmount - $discountAmount;

        return [
            'user_id' => User::factory(),
            'organization_id' => Organization::factory(),
            'client_id' => Client::factory(),
            'invoice_number' => 'INV-' . now()->format('Ym') . '-' . str_pad($this->faker->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'status' => 'draft',
            'issue_date' => now(),
            'due_date' => now()->addDays(30),
            'sent_at' => null,
            'paid_at' => null,
            'subtotal' => $subtotal,
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount,
            'discount_amount' => $discountAmount,
            'total' => $total,
            'currency' => 'USD',
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
            'payment_method' => null,
            'payment_instructions' => null,
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

    public function draft(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
            'sent_at' => null,
            'paid_at' => null,
        ]);
    }

    public function sent(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'sent',
            'sent_at' => now(),
            'paid_at' => null,
        ]);
    }

    public function paid(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'paid',
            'sent_at' => now()->subDays(5),
            'paid_at' => now(),
        ]);
    }

    public function overdue(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'overdue',
            'issue_date' => now()->subDays(60),
            'due_date' => now()->subDays(30),
            'sent_at' => now()->subDays(60),
            'paid_at' => null,
        ]);
    }

    public function cancelled(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
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
