<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Payroll;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * PayrollFactory
 *
 * Factory for creating Payroll model instances for testing.
 *
 * @extends Factory<Payroll>
 */
class PayrollFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Payroll>
     */
    protected $model = Payroll::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $periodStart = Carbon::parse(fake()->dateTimeBetween('-3 months', 'now'));
        $periodEnd = (clone $periodStart)->addDays(13); // 2-week period

        return [
            'organization_id' => Organization::factory(),
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'status' => Payroll::STATUS_DRAFT,
            'total_regular_hours' => fake()->randomFloat(2, 0, 320), // 0-320 hours (40h/week * 8 people)
            'total_overtime_hours' => fake()->randomFloat(2, 0, 40), // 0-40 overtime hours
            'total_earnings' => fake()->randomFloat(2, 1000, 50000), // $1k-$50k
            'currency' => fake()->randomElement(['USD', 'EUR', 'GBP', 'CAD']),
            'approved_at' => null,
            'approved_by' => null,
        ];
    }

    /**
     * Create a draft payroll (default state).
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => Payroll::STATUS_DRAFT,
            'approved_at' => null,
            'approved_by' => null,
        ]);
    }

    /**
     * Create an approved payroll.
     */
    public function approved(?string $userId = null): static
    {
        return $this->state(function (array $attributes) use ($userId): array {
            $approvedAt = Carbon::now()->subDays(fake()->numberBetween(1, 7));

            return [
                'status' => Payroll::STATUS_APPROVED,
                'approved_at' => $approvedAt,
                'approved_by' => $userId ?? User::factory(),
            ];
        });
    }

    /**
     * Create a paid payroll.
     */
    public function paid(?string $userId = null): static
    {
        return $this->state(function (array $attributes) use ($userId): array {
            $approvedAt = Carbon::now()->subDays(fake()->numberBetween(8, 14));

            return [
                'status' => Payroll::STATUS_PAID,
                'approved_at' => $approvedAt,
                'approved_by' => $userId ?? User::factory(),
            ];
        });
    }

    /**
     * Create payroll for a specific period.
     */
    public function forPeriod(Carbon $start, Carbon $end): static
    {
        return $this->state(fn (array $attributes): array => [
            'period_start' => $start,
            'period_end' => $end,
        ]);
    }

    /**
     * Create payroll for a specific organization.
     */
    public function forOrganization(string $organizationId): static
    {
        return $this->state(fn (array $attributes): array => [
            'organization_id' => $organizationId,
        ]);
    }

    /**
     * Create payroll with no overtime.
     */
    public function withoutOvertime(): static
    {
        return $this->state(fn (array $attributes): array => [
            'total_overtime_hours' => 0,
        ]);
    }

    /**
     * Create payroll with specific currency.
     */
    public function withCurrency(string $currency): static
    {
        return $this->state(fn (array $attributes): array => [
            'currency' => $currency,
        ]);
    }

    /**
     * Create payroll with zero earnings (no time entries).
     */
    public function empty(): static
    {
        return $this->state(fn (array $attributes): array => [
            'total_regular_hours' => 0,
            'total_overtime_hours' => 0,
            'total_earnings' => 0,
        ]);
    }

    /**
     * Create payroll for current bi-weekly period.
     */
    public function currentPeriod(): static
    {
        $now = Carbon::now();
        $periodStart = $now->startOfWeek();
        $periodEnd = (clone $periodStart)->addDays(13);

        return $this->state(fn (array $attributes): array => [
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
        ]);
    }

    /**
     * Create payroll for previous month.
     */
    public function lastMonth(): static
    {
        $periodStart = Carbon::now()->subMonth()->startOfMonth();
        $periodEnd = (clone $periodStart)->endOfMonth();

        return $this->state(fn (array $attributes): array => [
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
        ]);
    }
}
