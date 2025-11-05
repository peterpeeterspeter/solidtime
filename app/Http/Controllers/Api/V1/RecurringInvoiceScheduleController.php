<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\V1\RecurringSchedule\RecurringScheduleIndexRequest;
use App\Http\Requests\V1\RecurringSchedule\RecurringScheduleStoreRequest;
use App\Http\Requests\V1\RecurringSchedule\RecurringScheduleUpdateRequest;
use App\Http\Resources\V1\RecurringSchedule\RecurringInvoiceScheduleCollection;
use App\Http\Resources\V1\RecurringSchedule\RecurringInvoiceScheduleResource;
use App\Models\Organization;
use App\Models\RecurringInvoiceSchedule;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;

class RecurringInvoiceScheduleController extends Controller
{
    protected function checkPermission(Organization $organization, string $permission, ?RecurringInvoiceSchedule $schedule = null): void
    {
        parent::checkPermission($organization, $permission);
        if ($schedule !== null && $schedule->organization_id !== $organization->getKey()) {
            throw new AuthorizationException('Recurring schedule does not belong to organization');
        }
    }

    /**
     * Get recurring invoice schedules
     *
     * @return RecurringInvoiceScheduleCollection<RecurringInvoiceScheduleResource>
     *
     * @throws AuthorizationException
     *
     * @operationId getRecurringSchedules
     */
    public function index(Organization $organization, RecurringScheduleIndexRequest $request): RecurringInvoiceScheduleCollection
    {
        $this->checkPermission($organization, 'invoices:view');

        $schedulesQuery = RecurringInvoiceSchedule::query()
            ->whereBelongsTo($organization, 'organization')
            ->with(['client', 'project'])
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($status = $request->input('status')) {
            if ($status !== 'all') {
                $schedulesQuery->where('status', $status);
            }
        }

        // Filter by frequency
        if ($frequency = $request->input('frequency')) {
            $schedulesQuery->byFrequency($frequency);
        }

        // Filter by client
        if ($clientId = $request->input('client_id')) {
            $schedulesQuery->where('client_id', $clientId);
        }

        $schedules = $schedulesQuery->paginate(config('app.pagination_per_page_default'));

        return new RecurringInvoiceScheduleCollection($schedules);
    }

    /**
     * Get a single recurring invoice schedule
     *
     * @throws AuthorizationException
     *
     * @operationId getRecurringSchedule
     */
    public function show(Organization $organization, RecurringInvoiceSchedule $schedule): RecurringInvoiceScheduleResource
    {
        $this->checkPermission($organization, 'invoices:view', $schedule);

        $schedule->load(['client', 'project']);

        return new RecurringInvoiceScheduleResource($schedule);
    }

    /**
     * Create recurring invoice schedule
     *
     * @throws AuthorizationException
     *
     * @operationId createRecurringSchedule
     */
    public function store(Organization $organization, RecurringScheduleStoreRequest $request): RecurringInvoiceScheduleResource
    {
        $this->checkPermission($organization, 'invoices:create');

        $user = $this->user();

        $schedule = new RecurringInvoiceSchedule;
        $schedule->user_id = $user->id;
        $schedule->organization_id = $organization->id;
        $schedule->client_id = $request->input('client_id');
        $schedule->project_id = $request->input('project_id');
        $schedule->name = $request->input('name');
        $schedule->frequency = $request->input('frequency');
        $schedule->interval = $request->input('interval', 1);
        $schedule->day_of_month = $request->input('day_of_month');
        $schedule->day_of_week = $request->input('day_of_week');
        $schedule->start_date = $request->input('start_date');
        $schedule->end_date = $request->input('end_date');
        $schedule->next_generation_date = $request->input('start_date');
        $schedule->max_occurrences = $request->input('max_occurrences');
        $schedule->occurrences_count = 0;
        $schedule->status = 'active';
        $schedule->from_details = $request->input('from_details');
        $schedule->to_details = $request->input('to_details');
        $schedule->line_items = $request->input('line_items');
        $schedule->notes = $request->input('notes');
        $schedule->terms = $request->input('terms');
        $schedule->subtotal = $request->input('subtotal');
        $schedule->tax_rate = $request->input('tax_rate', 0);
        $schedule->tax_amount = $request->input('tax_amount', 0);
        $schedule->discount_amount = $request->input('discount_amount', 0);
        $schedule->total = $request->input('total');
        $schedule->currency = $request->input('currency', 'USD');
        $schedule->due_days = $request->input('due_days', 30);
        $schedule->due_date_type = $request->input('due_date_type', 'days_after_generation');
        $schedule->auto_send = $request->input('auto_send', false);
        $schedule->auto_charge = $request->input('auto_charge', false);
        $schedule->include_time_entries = $request->input('include_time_entries', false);
        $schedule->time_entries_from_date = $request->input('time_entries_from_date');
        $schedule->time_entries_to_date = $request->input('time_entries_to_date');
        $schedule->notify_on_generation = $request->input('notify_on_generation', false);
        $schedule->notification_emails = $request->input('notification_emails', []);
        $schedule->metadata = $request->input('metadata', []);
        $schedule->save();

        return new RecurringInvoiceScheduleResource($schedule);
    }

    /**
     * Update recurring invoice schedule
     *
     * @throws AuthorizationException
     *
     * @operationId updateRecurringSchedule
     */
    public function update(Organization $organization, RecurringInvoiceSchedule $schedule, RecurringScheduleUpdateRequest $request): RecurringInvoiceScheduleResource
    {
        $this->checkPermission($organization, 'invoices:update', $schedule);

        if ($request->has('client_id')) {
            $schedule->client_id = $request->input('client_id');
        }
        if ($request->has('project_id')) {
            $schedule->project_id = $request->input('project_id');
        }
        if ($request->has('name')) {
            $schedule->name = $request->input('name');
        }
        if ($request->has('frequency')) {
            $schedule->frequency = $request->input('frequency');
        }
        if ($request->has('interval')) {
            $schedule->interval = $request->input('interval');
        }
        if ($request->has('day_of_month')) {
            $schedule->day_of_month = $request->input('day_of_month');
        }
        if ($request->has('day_of_week')) {
            $schedule->day_of_week = $request->input('day_of_week');
        }
        if ($request->has('start_date')) {
            $schedule->start_date = $request->input('start_date');
        }
        if ($request->has('end_date')) {
            $schedule->end_date = $request->input('end_date');
        }
        if ($request->has('max_occurrences')) {
            $schedule->max_occurrences = $request->input('max_occurrences');
        }
        if ($request->has('from_details')) {
            $schedule->from_details = $request->input('from_details');
        }
        if ($request->has('to_details')) {
            $schedule->to_details = $request->input('to_details');
        }
        if ($request->has('line_items')) {
            $schedule->line_items = $request->input('line_items');
        }
        if ($request->has('notes')) {
            $schedule->notes = $request->input('notes');
        }
        if ($request->has('terms')) {
            $schedule->terms = $request->input('terms');
        }
        if ($request->has('subtotal')) {
            $schedule->subtotal = $request->input('subtotal');
        }
        if ($request->has('tax_rate')) {
            $schedule->tax_rate = $request->input('tax_rate');
        }
        if ($request->has('tax_amount')) {
            $schedule->tax_amount = $request->input('tax_amount');
        }
        if ($request->has('discount_amount')) {
            $schedule->discount_amount = $request->input('discount_amount');
        }
        if ($request->has('total')) {
            $schedule->total = $request->input('total');
        }
        if ($request->has('currency')) {
            $schedule->currency = $request->input('currency');
        }
        if ($request->has('due_days')) {
            $schedule->due_days = $request->input('due_days');
        }
        if ($request->has('due_date_type')) {
            $schedule->due_date_type = $request->input('due_date_type');
        }
        if ($request->has('auto_send')) {
            $schedule->auto_send = $request->input('auto_send');
        }
        if ($request->has('auto_charge')) {
            $schedule->auto_charge = $request->input('auto_charge');
        }
        if ($request->has('include_time_entries')) {
            $schedule->include_time_entries = $request->input('include_time_entries');
        }
        if ($request->has('notify_on_generation')) {
            $schedule->notify_on_generation = $request->input('notify_on_generation');
        }
        if ($request->has('notification_emails')) {
            $schedule->notification_emails = $request->input('notification_emails');
        }
        if ($request->has('metadata')) {
            $schedule->metadata = $request->input('metadata');
        }

        $schedule->save();

        return new RecurringInvoiceScheduleResource($schedule);
    }

    /**
     * Delete recurring invoice schedule
     *
     * @throws AuthorizationException
     *
     * @operationId deleteRecurringSchedule
     */
    public function destroy(Organization $organization, RecurringInvoiceSchedule $schedule): JsonResponse
    {
        $this->checkPermission($organization, 'invoices:delete', $schedule);

        $schedule->delete();

        return response()->json([
            'message' => 'Recurring schedule deleted successfully',
        ]);
    }

    /**
     * Pause recurring invoice schedule
     *
     * @throws AuthorizationException
     *
     * @operationId pauseRecurringSchedule
     */
    public function pause(Organization $organization, RecurringInvoiceSchedule $schedule): RecurringInvoiceScheduleResource
    {
        $this->checkPermission($organization, 'invoices:update', $schedule);

        $schedule->status = 'paused';
        $schedule->save();

        return new RecurringInvoiceScheduleResource($schedule);
    }

    /**
     * Resume recurring invoice schedule
     *
     * @throws AuthorizationException
     *
     * @operationId resumeRecurringSchedule
     */
    public function resume(Organization $organization, RecurringInvoiceSchedule $schedule): RecurringInvoiceScheduleResource
    {
        $this->checkPermission($organization, 'invoices:update', $schedule);

        $schedule->status = 'active';
        $schedule->save();

        return new RecurringInvoiceScheduleResource($schedule);
    }
}
