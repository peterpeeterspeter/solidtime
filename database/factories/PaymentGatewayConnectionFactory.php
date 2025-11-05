<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\PaymentGatewayConnection;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Crypt;

/**
 * @extends Factory<PaymentGatewayConnection>
 */
class PaymentGatewayConnectionFactory extends Factory
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
            'gateway' => $this->faker->randomElement(['stripe', 'paypal']),
            'gateway_account_id' => $this->faker->uuid(),
            'access_token' => Crypt::encryptString($this->faker->sha256()),
            'refresh_token' => Crypt::encryptString($this->faker->sha256()),
            'token_expires_at' => now()->addDays(30),
            'is_active' => true,
            'metadata' => null,
        ];
    }

    public function forUser(User $user): self
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->getKey(),
        ]);
    }

    public function stripe(): self
    {
        return $this->state(fn (array $attributes) => [
            'gateway' => 'stripe',
            'gateway_account_id' => 'acct_' . $this->faker->regexify('[A-Za-z0-9]{16}'),
        ]);
    }

    public function paypal(): self
    {
        return $this->state(fn (array $attributes) => [
            'gateway' => 'paypal',
            'gateway_account_id' => $this->faker->uuid(),
        ]);
    }

    public function inactive(): self
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function expired(): self
    {
        return $this->state(fn (array $attributes) => [
            'token_expires_at' => now()->subDays(1),
        ]);
    }
}
