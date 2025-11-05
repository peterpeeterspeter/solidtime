<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\PaymentGatewayConnection;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $amount = $this->faker->numberBetween(100, 10000);
        $feeAmount = round($amount * 0.029 + 0.30, 2); // Typical Stripe fee
        $netAmount = $amount - $feeAmount;

        return [
            'invoice_id' => Invoice::factory(),
            'user_id' => User::factory(),
            'organization_id' => Organization::factory(),
            'payment_gateway_connection_id' => PaymentGatewayConnection::factory(),
            'gateway' => $this->faker->randomElement(['stripe', 'paypal']),
            'gateway_transaction_id' => 'pi_' . $this->faker->regexify('[A-Za-z0-9]{24}'),
            'gateway_payment_method_id' => 'pm_' . $this->faker->regexify('[A-Za-z0-9]{24}'),
            'amount' => $amount,
            'currency' => 'USD',
            'fee_amount' => $feeAmount,
            'net_amount' => $netAmount,
            'status' => 'completed',
            'status_message' => null,
            'paid_at' => now(),
            'failed_at' => null,
            'refunded_at' => null,
            'refund_amount' => 0,
            'refund_reason' => null,
            'refund_transaction_id' => null,
            'customer_details' => [
                'name' => $this->faker->name(),
                'email' => $this->faker->email(),
            ],
            'gateway_response' => null,
            'metadata' => null,
        ];
    }

    public function forInvoice(Invoice $invoice): self
    {
        return $this->state(fn (array $attributes) => [
            'invoice_id' => $invoice->getKey(),
        ]);
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

    public function forGatewayConnection(PaymentGatewayConnection $connection): self
    {
        return $this->state(fn (array $attributes) => [
            'payment_gateway_connection_id' => $connection->getKey(),
            'gateway' => $connection->gateway,
        ]);
    }

    public function stripe(): self
    {
        return $this->state(fn (array $attributes) => [
            'gateway' => 'stripe',
            'gateway_transaction_id' => 'pi_' . $this->faker->regexify('[A-Za-z0-9]{24}'),
            'gateway_payment_method_id' => 'pm_' . $this->faker->regexify('[A-Za-z0-9]{24}'),
        ]);
    }

    public function paypal(): self
    {
        return $this->state(fn (array $attributes) => [
            'gateway' => 'paypal',
            'gateway_transaction_id' => $this->faker->regexify('[A-Z0-9]{17}'),
            'gateway_payment_method_id' => null,
        ]);
    }

    public function pending(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'paid_at' => null,
        ]);
    }

    public function processing(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'processing',
            'paid_at' => null,
        ]);
    }

    public function completed(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'paid_at' => now(),
        ]);
    }

    public function failed(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'status_message' => 'Payment failed: ' . $this->faker->sentence(),
            'paid_at' => null,
            'failed_at' => now(),
        ]);
    }

    public function refunded(): self
    {
        return $this->state(function (array $attributes): array {
            return [
                'status' => 'refunded',
                'refunded_at' => now(),
                'refund_amount' => $attributes['amount'],
                'refund_reason' => $this->faker->sentence(),
                'refund_transaction_id' => 're_' . $this->faker->regexify('[A-Za-z0-9]{24}'),
            ];
        });
    }

    public function partiallyRefunded(): self
    {
        return $this->state(function (array $attributes): array {
            $refundAmount = round($attributes['amount'] / 2, 2);

            return [
                'status' => 'partially_refunded',
                'refunded_at' => now(),
                'refund_amount' => $refundAmount,
                'refund_reason' => $this->faker->sentence(),
                'refund_transaction_id' => 're_' . $this->faker->regexify('[A-Za-z0-9]{24}'),
            ];
        });
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
