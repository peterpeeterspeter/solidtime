<?php

declare(strict_types=1);

namespace Tests\Unit\Service;

use App\Models\Member;
use App\Models\Organization;
use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Models\Project;
use App\Models\TimeEntry;
use App\Models\User;
use App\Services\PayrollService;
use App\Services\WebhookService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Services\PayrollService
 */
class PayrollServiceTest extends TestCase
{
    use RefreshDatabase;

    private PayrollService $payrollService;

    private WebhookService $webhookService;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock WebhookService to prevent actual webhook dispatches during tests
        $this->webhookService = Mockery::mock(WebhookService::class);
        $this->webhookService->shouldReceive('dispatch')->byDefault();

        $this->payrollService = new PayrollService($this->webhookService);
    }

    /**
     * @test
     */
    public function it_generates_payroll_for_organization(): void
    {
        $organization = Organization::factory()->create(['currency' => 'USD']);
        $periodStart = Carbon::parse('2025-01-01');
        $periodEnd = Carbon::parse('2025-01-14');

        $this->webhookService->shouldReceive('dispatch')
            ->once()
            ->with('payroll.generated', Mockery::type('array'));

        $payroll = $this->payrollService->generatePayroll(
            $organization->id,
            $periodStart,
            $periodEnd
        );

        $this->assertInstanceOf(Payroll::class, $payroll);
        $this->assertEquals($organization->id, $payroll->organization_id);
        $this->assertEquals('2025-01-01', $payroll->period_start->format('Y-m-d'));
        $this->assertEquals('2025-01-14', $payroll->period_end->format('Y-m-d'));
        $this->assertEquals(Payroll::STATUS_DRAFT, $payroll->status);
        $this->assertEquals('USD', $payroll->currency);
    }

    /**
     * @test
     */
    public function it_creates_payroll_items_for_members_with_time_entries(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $member = Member::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'billable_rate' => 50.00,
        ]);

        $project = Project::factory()->create(['organization_id' => $organization->id]);

        $periodStart = Carbon::parse('2025-01-01');
        $periodEnd = Carbon::parse('2025-01-14');

        // Create time entries (4 hours per day for 10 days = 40 hours)
        for ($i = 0; $i < 10; $i++) {
            $start = (clone $periodStart)->addDays($i)->setTime(9, 0, 0);
            $end = (clone $start)->addHours(4);

            TimeEntry::factory()->create([
                'user_id' => $user->id,
                'organization_id' => $organization->id,
                'project_id' => $project->id,
                'start' => $start,
                'end' => $end,
            ]);
        }

        $this->webhookService->shouldReceive('dispatch')
            ->once()
            ->with('payroll.generated', Mockery::type('array'));

        $payroll = $this->payrollService->generatePayroll(
            $organization->id,
            $periodStart,
            $periodEnd
        );

        $this->assertCount(1, $payroll->items);

        $item = $payroll->items->first();
        $this->assertEquals($member->id, $item->member_id);
        $this->assertEquals($user->id, $item->user_id);
        $this->assertEquals('40.00', $item->regular_hours);
        $this->assertEquals('0.00', $item->overtime_hours);
        $this->assertEquals('50.00', $item->hourly_rate);
        $this->assertEquals('2000.00', $item->total_earnings); // 40 * 50
    }

    /**
     * @test
     */
    public function it_calculates_overtime_when_exceeding_daily_threshold(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $member = Member::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'billable_rate' => 50.00,
        ]);

        $project = Project::factory()->create(['organization_id' => $organization->id]);

        $periodStart = Carbon::parse('2025-01-01');
        $periodEnd = Carbon::parse('2025-01-14');

        // Day 1: 10 hours (8 regular + 2 overtime)
        $start1 = (clone $periodStart)->setTime(9, 0, 0);
        $end1 = (clone $start1)->addHours(10);
        TimeEntry::factory()->create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'start' => $start1,
            'end' => $end1,
        ]);

        // Day 2: 6 hours (all regular)
        $start2 = (clone $periodStart)->addDays(1)->setTime(9, 0, 0);
        $end2 = (clone $start2)->addHours(6);
        TimeEntry::factory()->create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'start' => $start2,
            'end' => $end2,
        ]);

        $this->webhookService->shouldReceive('dispatch')->once();

        $payroll = $this->payrollService->generatePayroll(
            $organization->id,
            $periodStart,
            $periodEnd,
            8.0, // overtime threshold
            1.5  // overtime multiplier
        );

        $item = $payroll->items->first();
        $this->assertEquals('14.00', $item->regular_hours); // 8 + 6
        $this->assertEquals('2.00', $item->overtime_hours); // 10 - 8 = 2
        $this->assertEquals('50.00', $item->hourly_rate);
        $this->assertEquals('75.00', $item->overtime_rate); // 50 * 1.5
        $this->assertEquals('700.00', $item->regular_earnings); // 14 * 50
        $this->assertEquals('150.00', $item->overtime_earnings); // 2 * 75
        $this->assertEquals('850.00', $item->total_earnings); // 700 + 150
    }

    /**
     * @test
     */
    public function it_skips_members_without_billable_rate(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        Member::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'billable_rate' => null, // No billable rate
        ]);

        $periodStart = Carbon::parse('2025-01-01');
        $periodEnd = Carbon::parse('2025-01-14');

        $this->webhookService->shouldReceive('dispatch')->once();

        $payroll = $this->payrollService->generatePayroll(
            $organization->id,
            $periodStart,
            $periodEnd
        );

        $this->assertCount(0, $payroll->items);
        $this->assertEquals('0.00', $payroll->total_earnings);
    }

    /**
     * @test
     */
    public function it_skips_members_with_zero_billable_rate(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        Member::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'billable_rate' => 0,
        ]);

        $periodStart = Carbon::parse('2025-01-01');
        $periodEnd = Carbon::parse('2025-01-14');

        $this->webhookService->shouldReceive('dispatch')->once();

        $payroll = $this->payrollService->generatePayroll(
            $organization->id,
            $periodStart,
            $periodEnd
        );

        $this->assertCount(0, $payroll->items);
    }

    /**
     * @test
     */
    public function it_only_includes_time_entries_within_period(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $member = Member::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'billable_rate' => 50.00,
        ]);

        $project = Project::factory()->create(['organization_id' => $organization->id]);

        $periodStart = Carbon::parse('2025-01-01');
        $periodEnd = Carbon::parse('2025-01-14');

        // Entry within period (should be included)
        TimeEntry::factory()->create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'start' => Carbon::parse('2025-01-05 09:00:00'),
            'end' => Carbon::parse('2025-01-05 13:00:00'),
        ]);

        // Entry before period (should be excluded)
        TimeEntry::factory()->create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'start' => Carbon::parse('2024-12-31 09:00:00'),
            'end' => Carbon::parse('2024-12-31 13:00:00'),
        ]);

        // Entry after period (should be excluded)
        TimeEntry::factory()->create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'start' => Carbon::parse('2025-01-15 09:00:00'),
            'end' => Carbon::parse('2025-01-15 13:00:00'),
        ]);

        $this->webhookService->shouldReceive('dispatch')->once();

        $payroll = $this->payrollService->generatePayroll(
            $organization->id,
            $periodStart,
            $periodEnd
        );

        $item = $payroll->items->first();
        $this->assertEquals('4.00', $item->regular_hours); // Only 4 hours from the entry within period
    }

    /**
     * @test
     */
    public function it_approves_draft_payroll(): void
    {
        $user = User::factory()->create();
        $payroll = Payroll::factory()->draft()->create();

        $this->webhookService->shouldReceive('dispatch')
            ->once()
            ->with('payroll.approved', Mockery::type('array'));

        $result = $this->payrollService->approvePayroll($payroll, $user->id);

        $this->assertEquals(Payroll::STATUS_APPROVED, $result->status);
        $this->assertEquals($user->id, $result->approved_by);
        $this->assertNotNull($result->approved_at);
    }

    /**
     * @test
     */
    public function it_throws_exception_when_approving_non_draft_payroll(): void
    {
        $payroll = Payroll::factory()->approved()->create();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Only draft payrolls can be approved');

        $this->payrollService->approvePayroll($payroll, 'user-id');
    }

    /**
     * @test
     */
    public function it_marks_approved_payroll_as_paid(): void
    {
        $payroll = Payroll::factory()->approved()->create();

        $this->webhookService->shouldReceive('dispatch')
            ->once()
            ->with('payroll.paid', Mockery::type('array'));

        $result = $this->payrollService->markAsPaid($payroll);

        $this->assertEquals(Payroll::STATUS_PAID, $result->status);
    }

    /**
     * @test
     */
    public function it_throws_exception_when_marking_non_approved_payroll_as_paid(): void
    {
        $payroll = Payroll::factory()->draft()->create();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Only approved payrolls can be marked as paid');

        $this->payrollService->markAsPaid($payroll);
    }

    /**
     * @test
     */
    public function it_gets_payroll_summary_for_organization(): void
    {
        $organization = Organization::factory()->create();

        Payroll::factory()->draft()->forOrganization($organization->id)->create([
            'period_start' => Carbon::parse('2025-01-01'),
            'total_earnings' => 1000,
            'total_regular_hours' => 40,
            'total_overtime_hours' => 5,
        ]);

        Payroll::factory()->approved()->forOrganization($organization->id)->create([
            'period_start' => Carbon::parse('2025-01-15'),
            'total_earnings' => 2000,
            'total_regular_hours' => 80,
            'total_overtime_hours' => 10,
        ]);

        Payroll::factory()->paid()->forOrganization($organization->id)->create([
            'period_start' => Carbon::parse('2025-02-01'),
            'total_earnings' => 1500,
            'total_regular_hours' => 60,
            'total_overtime_hours' => 0,
        ]);

        $summary = $this->payrollService->getPayrollSummary(
            $organization->id,
            Carbon::parse('2025-01-01'),
            Carbon::parse('2025-12-31')
        );

        $this->assertEquals(3, $summary['total_payrolls']);
        $this->assertEquals(1, $summary['draft_count']);
        $this->assertEquals(1, $summary['approved_count']);
        $this->assertEquals(1, $summary['paid_count']);
        $this->assertEquals(4500, $summary['total_earnings']);
        $this->assertEquals(195, $summary['total_hours']); // 180 regular + 15 overtime
    }

    /**
     * @test
     */
    public function it_stores_time_entry_ids_in_payroll_item(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $member = Member::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'billable_rate' => 50.00,
        ]);

        $project = Project::factory()->create(['organization_id' => $organization->id]);

        $periodStart = Carbon::parse('2025-01-01');
        $periodEnd = Carbon::parse('2025-01-14');

        $entry1 = TimeEntry::factory()->create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'start' => Carbon::parse('2025-01-01 09:00:00'),
            'end' => Carbon::parse('2025-01-01 13:00:00'),
        ]);

        $entry2 = TimeEntry::factory()->create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'start' => Carbon::parse('2025-01-02 09:00:00'),
            'end' => Carbon::parse('2025-01-02 13:00:00'),
        ]);

        $this->webhookService->shouldReceive('dispatch')->once();

        $payroll = $this->payrollService->generatePayroll(
            $organization->id,
            $periodStart,
            $periodEnd
        );

        $item = $payroll->items->first();
        $this->assertIsArray($item->time_entry_ids);
        $this->assertContains($entry1->id, $item->time_entry_ids);
        $this->assertContains($entry2->id, $item->time_entry_ids);
    }
}
