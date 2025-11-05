<?php

declare(strict_types=1);

namespace Tests\Unit\Model;

use App\Models\Organization;
use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Models\Payroll
 */
class PayrollModelTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_has_uuid_primary_key(): void
    {
        $payroll = Payroll::factory()->create();

        $this->assertNotNull($payroll->id);
        $this->assertIsString($payroll->id);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $payroll->id);
    }

    /**
     * @test
     */
    public function it_belongs_to_organization(): void
    {
        $organization = Organization::factory()->create();
        $payroll = Payroll::factory()->forOrganization($organization->id)->create();

        $this->assertInstanceOf(Organization::class, $payroll->organization);
        $this->assertEquals($organization->id, $payroll->organization->id);
    }

    /**
     * @test
     */
    public function it_has_many_payroll_items(): void
    {
        $payroll = Payroll::factory()->create();
        PayrollItem::factory()->count(3)->forPayroll($payroll->id)->create();

        $this->assertCount(3, $payroll->items);
        $this->assertInstanceOf(PayrollItem::class, $payroll->items->first());
    }

    /**
     * @test
     */
    public function it_belongs_to_approver_user(): void
    {
        $user = User::factory()->create();
        $payroll = Payroll::factory()->approved($user->id)->create();

        $this->assertInstanceOf(User::class, $payroll->approver);
        $this->assertEquals($user->id, $payroll->approver->id);
    }

    /**
     * @test
     */
    public function it_casts_dates_correctly(): void
    {
        $periodStart = Carbon::parse('2025-01-01');
        $periodEnd = Carbon::parse('2025-01-15');

        $payroll = Payroll::factory()->forPeriod($periodStart, $periodEnd)->create();

        $this->assertInstanceOf(Carbon::class, $payroll->period_start);
        $this->assertInstanceOf(Carbon::class, $payroll->period_end);
        $this->assertEquals('2025-01-01', $payroll->period_start->format('Y-m-d'));
        $this->assertEquals('2025-01-15', $payroll->period_end->format('Y-m-d'));
    }

    /**
     * @test
     */
    public function it_defaults_to_draft_status(): void
    {
        $payroll = Payroll::factory()->create();

        $this->assertEquals(Payroll::STATUS_DRAFT, $payroll->status);
        $this->assertTrue($payroll->isDraft());
        $this->assertFalse($payroll->isApproved());
        $this->assertFalse($payroll->isPaid());
    }

    /**
     * @test
     */
    public function it_can_be_approved(): void
    {
        $payroll = Payroll::factory()->draft()->create();
        $user = User::factory()->create();

        $this->assertTrue($payroll->isDraft());

        $payroll->approve($user->id);

        $this->assertEquals(Payroll::STATUS_APPROVED, $payroll->status);
        $this->assertTrue($payroll->isApproved());
        $this->assertNotNull($payroll->approved_at);
        $this->assertEquals($user->id, $payroll->approved_by);
    }

    /**
     * @test
     */
    public function it_can_be_marked_as_paid(): void
    {
        $payroll = Payroll::factory()->approved()->create();

        $this->assertTrue($payroll->isApproved());

        $payroll->markAsPaid();

        $this->assertEquals(Payroll::STATUS_PAID, $payroll->status);
        $this->assertTrue($payroll->isPaid());
    }

    /**
     * @test
     */
    public function it_calculates_total_hours_attribute(): void
    {
        $payroll = Payroll::factory()->create([
            'total_regular_hours' => 80.00,
            'total_overtime_hours' => 10.00,
        ]);

        $this->assertEquals(90.00, $payroll->total_hours);
    }

    /**
     * @test
     */
    public function it_calculates_average_hourly_rate_attribute(): void
    {
        $payroll = Payroll::factory()->create([
            'total_regular_hours' => 80.00,
            'total_overtime_hours' => 20.00,
            'total_earnings' => 5000.00,
        ]);

        // 5000 / 100 = 50
        $this->assertEquals(50.00, $payroll->average_hourly_rate);
    }

    /**
     * @test
     */
    public function it_returns_zero_average_hourly_rate_when_no_hours(): void
    {
        $payroll = Payroll::factory()->empty()->create();

        $this->assertEquals(0, $payroll->average_hourly_rate);
    }

    /**
     * @test
     */
    public function it_generates_period_label_attribute(): void
    {
        $periodStart = Carbon::parse('2025-01-01');
        $periodEnd = Carbon::parse('2025-01-15');

        $payroll = Payroll::factory()->forPeriod($periodStart, $periodEnd)->create();

        $this->assertEquals('Jan 1 - Jan 15, 2025', $payroll->period_label);
    }

    /**
     * @test
     */
    public function it_can_scope_by_status(): void
    {
        Payroll::factory()->draft()->create();
        Payroll::factory()->approved()->create();
        Payroll::factory()->paid()->create();

        $this->assertCount(1, Payroll::status(Payroll::STATUS_DRAFT)->get());
        $this->assertCount(1, Payroll::status(Payroll::STATUS_APPROVED)->get());
        $this->assertCount(1, Payroll::status(Payroll::STATUS_PAID)->get());
    }

    /**
     * @test
     */
    public function it_can_scope_by_period(): void
    {
        $start = Carbon::parse('2025-01-01');
        $end = Carbon::parse('2025-01-31');

        Payroll::factory()->forPeriod($start, $end)->create();
        Payroll::factory()->forPeriod(Carbon::parse('2024-12-01'), Carbon::parse('2024-12-31'))->create();

        $payrolls = Payroll::forPeriod($start, $end)->get();

        $this->assertCount(1, $payrolls);
    }

    /**
     * @test
     */
    public function it_can_scope_by_organization(): void
    {
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();

        Payroll::factory()->forOrganization($org1->id)->count(2)->create();
        Payroll::factory()->forOrganization($org2->id)->create();

        $this->assertCount(2, Payroll::forOrganization($org1->id)->get());
        $this->assertCount(1, Payroll::forOrganization($org2->id)->get());
    }

    /**
     * @test
     */
    public function it_casts_decimal_values_correctly(): void
    {
        $payroll = Payroll::factory()->create([
            'total_regular_hours' => 80.50,
            'total_overtime_hours' => 10.75,
            'total_earnings' => 5432.10,
        ]);

        $this->assertEquals('80.50', $payroll->total_regular_hours);
        $this->assertEquals('10.75', $payroll->total_overtime_hours);
        $this->assertEquals('5432.10', $payroll->total_earnings);
    }

    /**
     * @test
     */
    public function it_stores_currency_code(): void
    {
        $payroll = Payroll::factory()->withCurrency('EUR')->create();

        $this->assertEquals('EUR', $payroll->currency);
    }
}
