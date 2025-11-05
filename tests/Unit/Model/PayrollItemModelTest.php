<?php

declare(strict_types=1);

namespace Tests\Unit\Model;

use App\Models\Member;
use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Models\PayrollItem
 */
class PayrollItemModelTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_has_uuid_primary_key(): void
    {
        $item = PayrollItem::factory()->create();

        $this->assertNotNull($item->id);
        $this->assertIsString($item->id);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $item->id);
    }

    /**
     * @test
     */
    public function it_belongs_to_payroll(): void
    {
        $payroll = Payroll::factory()->create();
        $item = PayrollItem::factory()->forPayroll($payroll->id)->create();

        $this->assertInstanceOf(Payroll::class, $item->payroll);
        $this->assertEquals($payroll->id, $item->payroll->id);
    }

    /**
     * @test
     */
    public function it_belongs_to_member(): void
    {
        $member = Member::factory()->create();
        $item = PayrollItem::factory()->forMember($member->id, $member->user_id)->create();

        $this->assertInstanceOf(Member::class, $item->member);
        $this->assertEquals($member->id, $item->member->id);
    }

    /**
     * @test
     */
    public function it_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $member = Member::factory()->create(['user_id' => $user->id]);
        $item = PayrollItem::factory()->forMember($member->id, $user->id)->create();

        $this->assertInstanceOf(User::class, $item->user);
        $this->assertEquals($user->id, $item->user->id);
    }

    /**
     * @test
     */
    public function it_calculates_total_hours_attribute(): void
    {
        $item = PayrollItem::factory()->create([
            'regular_hours' => 80.00,
            'overtime_hours' => 10.00,
        ]);

        $this->assertEquals(90.00, $item->total_hours);
    }

    /**
     * @test
     */
    public function it_calculates_effective_hourly_rate_attribute(): void
    {
        $item = PayrollItem::factory()->create([
            'regular_hours' => 80.00,
            'overtime_hours' => 20.00,
            'total_earnings' => 5000.00,
        ]);

        // 5000 / 100 = 50
        $this->assertEquals(50.00, $item->effective_hourly_rate);
    }

    /**
     * @test
     */
    public function it_returns_zero_effective_hourly_rate_when_no_hours(): void
    {
        $item = PayrollItem::factory()->empty()->create();

        $this->assertEquals(0, $item->effective_hourly_rate);
    }

    /**
     * @test
     */
    public function it_calculates_overtime_percentage_attribute(): void
    {
        $item = PayrollItem::factory()->create([
            'regular_hours' => 80.00,
            'overtime_hours' => 20.00,
        ]);

        // 20 / 100 * 100 = 20%
        $this->assertEquals(20.00, $item->overtime_percentage);
    }

    /**
     * @test
     */
    public function it_returns_zero_overtime_percentage_when_no_hours(): void
    {
        $item = PayrollItem::factory()->empty()->create();

        $this->assertEquals(0, $item->overtime_percentage);
    }

    /**
     * @test
     */
    public function it_detects_if_has_overtime(): void
    {
        $itemWithOvertime = PayrollItem::factory()->withHighOvertime()->create();
        $itemWithoutOvertime = PayrollItem::factory()->withoutOvertime()->create();

        $this->assertTrue($itemWithOvertime->hasOvertime());
        $this->assertFalse($itemWithoutOvertime->hasOvertime());
    }

    /**
     * @test
     */
    public function it_can_scope_with_overtime(): void
    {
        PayrollItem::factory()->withHighOvertime()->count(2)->create();
        PayrollItem::factory()->withoutOvertime()->count(3)->create();

        $this->assertCount(2, PayrollItem::withOvertime()->get());
    }

    /**
     * @test
     */
    public function it_can_scope_by_member(): void
    {
        $member1 = Member::factory()->create();
        $member2 = Member::factory()->create();

        PayrollItem::factory()->forMember($member1->id, $member1->user_id)->count(2)->create();
        PayrollItem::factory()->forMember($member2->id, $member2->user_id)->create();

        $this->assertCount(2, PayrollItem::forMember($member1->id)->get());
        $this->assertCount(1, PayrollItem::forMember($member2->id)->get());
    }

    /**
     * @test
     */
    public function it_can_scope_by_user(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $member1 = Member::factory()->create(['user_id' => $user1->id]);
        $member2 = Member::factory()->create(['user_id' => $user2->id]);

        PayrollItem::factory()->forMember($member1->id, $user1->id)->count(2)->create();
        PayrollItem::factory()->forMember($member2->id, $user2->id)->create();

        $this->assertCount(2, PayrollItem::forUser($user1->id)->get());
        $this->assertCount(1, PayrollItem::forUser($user2->id)->get());
    }

    /**
     * @test
     */
    public function it_casts_decimal_values_correctly(): void
    {
        $item = PayrollItem::factory()->create([
            'regular_hours' => 80.50,
            'overtime_hours' => 10.75,
            'hourly_rate' => 45.00,
            'overtime_rate' => 67.50,
            'regular_earnings' => 3622.50,
            'overtime_earnings' => 725.63,
            'total_earnings' => 4348.13,
        ]);

        $this->assertEquals('80.50', $item->regular_hours);
        $this->assertEquals('10.75', $item->overtime_hours);
        $this->assertEquals('45.00', $item->hourly_rate);
        $this->assertEquals('67.50', $item->overtime_rate);
        $this->assertEquals('3622.50', $item->regular_earnings);
        $this->assertEquals('725.63', $item->overtime_earnings);
        $this->assertEquals('4348.13', $item->total_earnings);
    }

    /**
     * @test
     */
    public function it_stores_time_entry_ids_as_json_array(): void
    {
        $timeEntryIds = ['entry-1', 'entry-2', 'entry-3'];
        $item = PayrollItem::factory()->withTimeEntries($timeEntryIds)->create();

        $this->assertIsArray($item->time_entry_ids);
        $this->assertCount(3, $item->time_entry_ids);
        $this->assertEquals($timeEntryIds, $item->time_entry_ids);
    }

    /**
     * @test
     */
    public function it_can_have_null_time_entry_ids(): void
    {
        $item = PayrollItem::factory()->create(['time_entry_ids' => null]);

        $this->assertNull($item->time_entry_ids);
    }

    /**
     * @test
     */
    public function it_calculates_earnings_correctly_with_overtime(): void
    {
        $item = PayrollItem::factory()
            ->withHourlyRate(50.00, 1.5)
            ->create([
                'regular_hours' => 80.00,
                'overtime_hours' => 10.00,
            ]);

        // Regular: 80 * 50 = 4000
        // Overtime: 10 * 75 = 750
        // Total: 4750
        $this->assertEquals('4000.00', $item->regular_earnings);
        $this->assertEquals('750.00', $item->overtime_earnings);
        $this->assertEquals('4750.00', $item->total_earnings);
    }

    /**
     * @test
     */
    public function it_handles_zero_hours_correctly(): void
    {
        $item = PayrollItem::factory()->empty()->create();

        $this->assertEquals('0.00', $item->regular_hours);
        $this->assertEquals('0.00', $item->overtime_hours);
        $this->assertEquals('0.00', $item->total_earnings);
        $this->assertEquals(0, $item->total_hours);
        $this->assertFalse($item->hasOvertime());
    }
}
