<?php

declare(strict_types=1);

namespace Tests\Unit\Endpoint\Api\V1;

use App\Models\Member;
use App\Models\Organization;
use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Models\Project;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Passport\Passport;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Http\Controllers\Api\V1\PayrollController
 */
class PayrollEndpointTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->organization = Organization::factory()->create();
        Member::factory()->create([
            'user_id' => $this->user->id,
            'organization_id' => $this->organization->id,
            'role' => 'owner',
        ]);

        Passport::actingAs($this->user);
    }

    /**
     * @test
     */
    public function it_lists_payrolls_for_organization(): void
    {
        Payroll::factory()->count(3)->forOrganization($this->organization->id)->create();

        $response = $this->getJson("/api/v1/organizations/{$this->organization->id}/payrolls");

        $response->assertOk();
        $response->assertJsonCount(3, 'data');
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'period_start',
                    'period_end',
                    'status',
                    'total_regular_hours',
                    'total_overtime_hours',
                    'total_earnings',
                    'currency',
                ],
            ],
            'meta' => ['total', 'per_page', 'current_page'],
        ]);
    }

    /**
     * @test
     */
    public function it_filters_payrolls_by_status(): void
    {
        Payroll::factory()->draft()->forOrganization($this->organization->id)->create();
        Payroll::factory()->approved()->forOrganization($this->organization->id)->create();
        Payroll::factory()->paid()->forOrganization($this->organization->id)->create();

        $response = $this->getJson("/api/v1/organizations/{$this->organization->id}/payrolls?status=draft");

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.status', 'draft');
    }

    /**
     * @test
     */
    public function it_shows_payroll_details_with_items(): void
    {
        $payroll = Payroll::factory()->forOrganization($this->organization->id)->create();
        PayrollItem::factory()->count(2)->forPayroll($payroll->id)->create();

        $response = $this->getJson("/api/v1/organizations/{$this->organization->id}/payrolls/{$payroll->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'period_start',
                'period_end',
                'status',
                'total_earnings',
                'items' => [
                    '*' => [
                        'id',
                        'user_id',
                        'regular_hours',
                        'overtime_hours',
                        'hourly_rate',
                        'total_earnings',
                    ],
                ],
            ],
        ]);
        $response->assertJsonCount(2, 'data.items');
    }

    /**
     * @test
     */
    public function it_generates_new_payroll(): void
    {
        $member = Member::factory()->create([
            'organization_id' => $this->organization->id,
            'billable_rate' => 50.00,
        ]);

        $project = Project::factory()->create(['organization_id' => $this->organization->id]);

        // Create time entries
        TimeEntry::factory()->create([
            'user_id' => $member->user_id,
            'organization_id' => $this->organization->id,
            'project_id' => $project->id,
            'start' => Carbon::parse('2025-01-01 09:00:00'),
            'end' => Carbon::parse('2025-01-01 17:00:00'), // 8 hours
        ]);

        $response = $this->postJson("/api/v1/organizations/{$this->organization->id}/payrolls", [
            'period_start' => '2025-01-01',
            'period_end' => '2025-01-14',
            'overtime_threshold' => 8.0,
            'overtime_multiplier' => 1.5,
        ]);

        $response->assertCreated();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'period_start',
                'period_end',
                'status',
                'total_earnings',
            ],
            'message',
        ]);
        $response->assertJsonPath('data.status', 'draft');
        $response->assertJsonPath('data.period_start', '2025-01-01');
        $response->assertJsonPath('data.period_end', '2025-01-14');

        $this->assertDatabaseHas('payrolls', [
            'organization_id' => $this->organization->id,
            'period_start' => '2025-01-01',
            'period_end' => '2025-01-14',
            'status' => 'draft',
        ]);
    }

    /**
     * @test
     */
    public function it_requires_period_start_date(): void
    {
        $response = $this->postJson("/api/v1/organizations/{$this->organization->id}/payrolls", [
            'period_end' => '2025-01-14',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['period_start']);
    }

    /**
     * @test
     */
    public function it_requires_period_end_date(): void
    {
        $response = $this->postJson("/api/v1/organizations/{$this->organization->id}/payrolls", [
            'period_start' => '2025-01-01',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['period_end']);
    }

    /**
     * @test
     */
    public function it_validates_period_end_is_after_period_start(): void
    {
        $response = $this->postJson("/api/v1/organizations/{$this->organization->id}/payrolls", [
            'period_start' => '2025-01-15',
            'period_end' => '2025-01-01',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['period_end']);
    }

    /**
     * @test
     */
    public function it_prevents_duplicate_payroll_for_same_period(): void
    {
        Payroll::factory()->forOrganization($this->organization->id)->create([
            'period_start' => Carbon::parse('2025-01-01'),
            'period_end' => Carbon::parse('2025-01-14'),
        ]);

        $response = $this->postJson("/api/v1/organizations/{$this->organization->id}/payrolls", [
            'period_start' => '2025-01-01',
            'period_end' => '2025-01-14',
        ]);

        $response->assertStatus(409);
        $response->assertJson(['error' => 'Payroll for this period already exists for this organization']);
    }

    /**
     * @test
     */
    public function it_approves_draft_payroll(): void
    {
        $payroll = Payroll::factory()->draft()->forOrganization($this->organization->id)->create();

        $response = $this->postJson("/api/v1/organizations/{$this->organization->id}/payrolls/{$payroll->id}/approve");

        $response->assertOk();
        $response->assertJsonPath('data.status', 'approved');
        $response->assertJsonPath('data.approved_by', $this->user->id);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'status',
                'approved_at',
                'approved_by',
            ],
            'message',
        ]);

        $this->assertDatabaseHas('payrolls', [
            'id' => $payroll->id,
            'status' => 'approved',
            'approved_by' => $this->user->id,
        ]);
    }

    /**
     * @test
     */
    public function it_prevents_approving_non_draft_payroll(): void
    {
        $payroll = Payroll::factory()->approved()->forOrganization($this->organization->id)->create();

        $response = $this->postJson("/api/v1/organizations/{$this->organization->id}/payrolls/{$payroll->id}/approve");

        $response->assertStatus(400);
        $response->assertJson(['error' => 'Only draft payrolls can be approved']);
    }

    /**
     * @test
     */
    public function it_marks_approved_payroll_as_paid(): void
    {
        $payroll = Payroll::factory()->approved()->forOrganization($this->organization->id)->create();

        $response = $this->postJson("/api/v1/organizations/{$this->organization->id}/payrolls/{$payroll->id}/mark-as-paid");

        $response->assertOk();
        $response->assertJsonPath('data.status', 'paid');
        $response->assertJsonStructure([
            'data' => [
                'id',
                'status',
            ],
            'message',
        ]);

        $this->assertDatabaseHas('payrolls', [
            'id' => $payroll->id,
            'status' => 'paid',
        ]);
    }

    /**
     * @test
     */
    public function it_prevents_marking_non_approved_payroll_as_paid(): void
    {
        $payroll = Payroll::factory()->draft()->forOrganization($this->organization->id)->create();

        $response = $this->postJson("/api/v1/organizations/{$this->organization->id}/payrolls/{$payroll->id}/mark-as-paid");

        $response->assertStatus(400);
        $response->assertJson(['error' => 'Only approved payrolls can be marked as paid']);
    }

    /**
     * @test
     */
    public function it_deletes_draft_payroll(): void
    {
        $payroll = Payroll::factory()->draft()->forOrganization($this->organization->id)->create();

        $response = $this->deleteJson("/api/v1/organizations/{$this->organization->id}/payrolls/{$payroll->id}");

        $response->assertOk();
        $response->assertJson(['message' => 'Payroll deleted successfully']);

        $this->assertDatabaseMissing('payrolls', [
            'id' => $payroll->id,
        ]);
    }

    /**
     * @test
     */
    public function it_prevents_deleting_approved_payroll(): void
    {
        $payroll = Payroll::factory()->approved()->forOrganization($this->organization->id)->create();

        $response = $this->deleteJson("/api/v1/organizations/{$this->organization->id}/payrolls/{$payroll->id}");

        $response->assertStatus(400);
        $response->assertJson(['error' => 'Only draft payrolls can be deleted']);

        $this->assertDatabaseHas('payrolls', [
            'id' => $payroll->id,
        ]);
    }

    /**
     * @test
     */
    public function it_prevents_deleting_paid_payroll(): void
    {
        $payroll = Payroll::factory()->paid()->forOrganization($this->organization->id)->create();

        $response = $this->deleteJson("/api/v1/organizations/{$this->organization->id}/payrolls/{$payroll->id}");

        $response->assertStatus(400);
        $response->assertJson(['error' => 'Only draft payrolls can be deleted']);
    }

    /**
     * @test
     */
    public function it_gets_payroll_summary(): void
    {
        Payroll::factory()->draft()->forOrganization($this->organization->id)->create([
            'period_start' => Carbon::parse('2025-01-01'),
            'total_earnings' => 1000,
        ]);

        Payroll::factory()->approved()->forOrganization($this->organization->id)->create([
            'period_start' => Carbon::parse('2025-01-15'),
            'total_earnings' => 2000,
        ]);

        $response = $this->getJson("/api/v1/organizations/{$this->organization->id}/payrolls/summary?period_start=2025-01-01&period_end=2025-01-31");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'total_payrolls',
                'draft_count',
                'approved_count',
                'paid_count',
                'total_earnings',
                'total_hours',
            ],
        ]);
        $response->assertJsonPath('data.total_payrolls', 2);
        $response->assertJsonPath('data.total_earnings', 3000);
    }

    /**
     * @test
     */
    public function it_requires_permission_to_view_payrolls(): void
    {
        $otherUser = User::factory()->create();
        Passport::actingAs($otherUser);

        $response = $this->getJson("/api/v1/organizations/{$this->organization->id}/payrolls");

        $response->assertForbidden();
    }

    /**
     * @test
     */
    public function it_requires_permission_to_create_payroll(): void
    {
        $member = Member::factory()->create([
            'organization_id' => $this->organization->id,
            'role' => 'employee', // Not an owner/manager
        ]);
        $otherUser = User::factory()->create();
        Member::factory()->create([
            'user_id' => $otherUser->id,
            'organization_id' => $this->organization->id,
            'role' => 'employee',
        ]);

        Passport::actingAs($otherUser);

        $response = $this->postJson("/api/v1/organizations/{$this->organization->id}/payrolls", [
            'period_start' => '2025-01-01',
            'period_end' => '2025-01-14',
        ]);

        $response->assertForbidden();
    }

    /**
     * @test
     */
    public function it_paginates_payroll_list(): void
    {
        Payroll::factory()->count(25)->forOrganization($this->organization->id)->create();

        $response = $this->getJson("/api/v1/organizations/{$this->organization->id}/payrolls?per_page=10");

        $response->assertOk();
        $response->assertJsonCount(10, 'data');
        $response->assertJsonPath('meta.per_page', 10);
        $response->assertJsonPath('meta.total', 25);
        $response->assertJsonStructure([
            'links' => ['first', 'last', 'prev', 'next'],
        ]);
    }

    /**
     * @test
     */
    public function it_filters_payrolls_by_date_range(): void
    {
        Payroll::factory()->forOrganization($this->organization->id)->create([
            'period_start' => Carbon::parse('2025-01-01'),
            'period_end' => Carbon::parse('2025-01-14'),
        ]);

        Payroll::factory()->forOrganization($this->organization->id)->create([
            'period_start' => Carbon::parse('2025-02-01'),
            'period_end' => Carbon::parse('2025-02-14'),
        ]);

        Payroll::factory()->forOrganization($this->organization->id)->create([
            'period_start' => Carbon::parse('2025-03-01'),
            'period_end' => Carbon::parse('2025-03-14'),
        ]);

        $response = $this->getJson("/api/v1/organizations/{$this->organization->id}/payrolls?period_start=2025-01-01&period_end=2025-02-28");

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
    }

    /**
     * @test
     */
    public function it_validates_overtime_threshold_range(): void
    {
        $response = $this->postJson("/api/v1/organizations/{$this->organization->id}/payrolls", [
            'period_start' => '2025-01-01',
            'period_end' => '2025-01-14',
            'overtime_threshold' => 30, // Invalid: > 24
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['overtime_threshold']);
    }

    /**
     * @test
     */
    public function it_validates_overtime_multiplier_range(): void
    {
        $response = $this->postJson("/api/v1/organizations/{$this->organization->id}/payrolls", [
            'period_start' => '2025-01-01',
            'period_end' => '2025-01-14',
            'overtime_multiplier' => 5, // Invalid: > 3
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['overtime_multiplier']);
    }
}
