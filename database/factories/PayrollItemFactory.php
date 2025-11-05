<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Member;
use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * PayrollItemFactory
 *
 * Factory for creating PayrollItem model instances for testing.
 *
 * @extends Factory<PayrollItem>
 */
class PayrollItemFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<PayrollItem>
     */
    protected $model = PayrollItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $regularHours = fake()->randomFloat(2, 40, 80); // 40-80 regular hours
        $overtimeHours = fake()->randomFloat(2, 0, 10); // 0-10 overtime hours
        $hourlyRate = fake()->randomFloat(2, 15, 150); // $15-$150/hour
        $overtimeMultiplier = 1.5;
        $overtimeRate = $hourlyRate * $overtimeMultiplier;
        $regularEarnings = $regularHours * $hourlyRate;
        $overtimeEarnings = $overtimeHours * $overtimeRate;
        $totalEarnings = $regularEarnings + $overtimeEarnings;

        return [
            'payroll_id' => Payroll::factory(),
            'member_id' => Member::factory(),
            'user_id' => User::factory(),
            'regular_hours' => round($regularHours, 2),
            'overtime_hours' => round($overtimeHours, 2),
            'hourly_rate' => round($hourlyRate, 2),
            'overtime_rate' => round($overtimeRate, 2),
            'regular_earnings' => round($regularEarnings, 2),
            'overtime_earnings' => round($overtimeEarnings, 2),
            'total_earnings' => round($totalEarnings, 2),
            'time_entry_ids' => [], // Empty by default
        ];
    }

    /**
     * Create item for a specific payroll.
     */
    public function forPayroll(string $payrollId): static
    {
        return $this->state(fn (array $attributes): array => [
            'payroll_id' => $payrollId,
        ]);
    }

    /**
     * Create item for a specific member.
     */
    public function forMember(string $memberId, string $userId): static
    {
        return $this->state(fn (array $attributes): array => [
            'member_id' => $memberId,
            'user_id' => $userId,
        ]);
    }

    /**
     * Create item with no overtime.
     */
    public function withoutOvertime(): static
    {
        return $this->state(function (array $attributes): array {
            $regularHours = $attributes['regular_hours'];
            $hourlyRate = $attributes['hourly_rate'];
            $regularEarnings = $regularHours * $hourlyRate;

            return [
                'overtime_hours' => 0,
                'overtime_earnings' => 0,
                'total_earnings' => round($regularEarnings, 2),
            ];
        });
    }

    /**
     * Create item with significant overtime.
     */
    public function withHighOvertime(): static
    {
        return $this->state(function (array $attributes): array {
            $regularHours = fake()->randomFloat(2, 80, 80); // Full 2 weeks at 8h/day
            $overtimeHours = fake()->randomFloat(2, 20, 40); // 20-40 overtime hours
            $hourlyRate = $attributes['hourly_rate'];
            $overtimeRate = $hourlyRate * 1.5;
            $regularEarnings = $regularHours * $hourlyRate;
            $overtimeEarnings = $overtimeHours * $overtimeRate;
            $totalEarnings = $regularEarnings + $overtimeEarnings;

            return [
                'regular_hours' => round($regularHours, 2),
                'overtime_hours' => round($overtimeHours, 2),
                'overtime_rate' => round($overtimeRate, 2),
                'regular_earnings' => round($regularEarnings, 2),
                'overtime_earnings' => round($overtimeEarnings, 2),
                'total_earnings' => round($totalEarnings, 2),
            ];
        });
    }

    /**
     * Create item with specific hourly rate.
     */
    public function withHourlyRate(float $rate, float $overtimeMultiplier = 1.5): static
    {
        return $this->state(function (array $attributes) use ($rate, $overtimeMultiplier): array {
            $regularHours = $attributes['regular_hours'];
            $overtimeHours = $attributes['overtime_hours'];
            $overtimeRate = $rate * $overtimeMultiplier;
            $regularEarnings = $regularHours * $rate;
            $overtimeEarnings = $overtimeHours * $overtimeRate;
            $totalEarnings = $regularEarnings + $overtimeEarnings;

            return [
                'hourly_rate' => round($rate, 2),
                'overtime_rate' => round($overtimeRate, 2),
                'regular_earnings' => round($regularEarnings, 2),
                'overtime_earnings' => round($overtimeEarnings, 2),
                'total_earnings' => round($totalEarnings, 2),
            ];
        });
    }

    /**
     * Create item with specific time entries.
     */
    public function withTimeEntries(array $timeEntryIds): static
    {
        return $this->state(fn (array $attributes): array => [
            'time_entry_ids' => $timeEntryIds,
        ]);
    }

    /**
     * Create item with zero hours (no work).
     */
    public function empty(): static
    {
        return $this->state(fn (array $attributes): array => [
            'regular_hours' => 0,
            'overtime_hours' => 0,
            'regular_earnings' => 0,
            'overtime_earnings' => 0,
            'total_earnings' => 0,
            'time_entry_ids' => [],
        ]);
    }

    /**
     * Create item with standard full-time hours (80 hours over 2 weeks).
     */
    public function fullTime(): static
    {
        return $this->state(function (array $attributes): array {
            $regularHours = 80.00; // 2 weeks * 40 hours/week
            $overtimeHours = 0;
            $hourlyRate = $attributes['hourly_rate'];
            $regularEarnings = $regularHours * $hourlyRate;

            return [
                'regular_hours' => $regularHours,
                'overtime_hours' => $overtimeHours,
                'regular_earnings' => round($regularEarnings, 2),
                'overtime_earnings' => 0,
                'total_earnings' => round($regularEarnings, 2),
            ];
        });
    }

    /**
     * Create item with part-time hours (40 hours over 2 weeks).
     */
    public function partTime(): static
    {
        return $this->state(function (array $attributes): array {
            $regularHours = 40.00; // 2 weeks * 20 hours/week
            $overtimeHours = 0;
            $hourlyRate = $attributes['hourly_rate'];
            $regularEarnings = $regularHours * $hourlyRate;

            return [
                'regular_hours' => $regularHours,
                'overtime_hours' => $overtimeHours,
                'regular_earnings' => round($regularEarnings, 2),
                'overtime_earnings' => 0,
                'total_earnings' => round($regularEarnings, 2),
            ];
        });
    }
}
