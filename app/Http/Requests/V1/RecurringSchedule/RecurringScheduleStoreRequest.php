<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\RecurringSchedule;

use App\Http\Requests\V1\BaseFormRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class RecurringScheduleStoreRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<string|ValidationRule>>
     */
    public function rules(): array
    {
        return [
            'client_id' => [
                'required',
                'string',
                'uuid',
                'exists:clients,id',
            ],
            'project_id' => [
                'nullable',
                'string',
                'uuid',
                'exists:projects,id',
            ],
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'frequency' => [
                'required',
                'string',
                'in:daily,weekly,biweekly,monthly,quarterly,biannually,annually',
            ],
            'interval' => [
                'nullable',
                'integer',
                'min:1',
            ],
            'day_of_month' => [
                'nullable',
                'integer',
                'min:1',
                'max:31',
            ],
            'day_of_week' => [
                'nullable',
                'integer',
                'min:0',
                'max:6',
            ],
            'start_date' => [
                'required',
                'date',
            ],
            'end_date' => [
                'nullable',
                'date',
                'after:start_date',
            ],
            'max_occurrences' => [
                'nullable',
                'integer',
                'min:1',
            ],
            'from_details' => [
                'required',
                'array',
            ],
            'to_details' => [
                'required',
                'array',
            ],
            'line_items' => [
                'required',
                'array',
                'min:1',
            ],
            'notes' => [
                'nullable',
                'string',
            ],
            'terms' => [
                'nullable',
                'string',
            ],
            'subtotal' => [
                'required',
                'numeric',
                'min:0',
            ],
            'tax_rate' => [
                'nullable',
                'numeric',
                'min:0',
                'max:100',
            ],
            'tax_amount' => [
                'nullable',
                'numeric',
                'min:0',
            ],
            'discount_amount' => [
                'nullable',
                'numeric',
                'min:0',
            ],
            'total' => [
                'required',
                'numeric',
                'min:0',
            ],
            'currency' => [
                'nullable',
                'string',
                'size:3',
            ],
            'due_days' => [
                'nullable',
                'integer',
                'min:0',
            ],
            'due_date_type' => [
                'nullable',
                'string',
                'in:days_after_generation,specific_day',
            ],
            'auto_send' => [
                'nullable',
                'boolean',
            ],
            'auto_charge' => [
                'nullable',
                'boolean',
            ],
            'include_time_entries' => [
                'nullable',
                'boolean',
            ],
            'time_entries_from_date' => [
                'nullable',
                'date',
            ],
            'time_entries_to_date' => [
                'nullable',
                'date',
            ],
            'notify_on_generation' => [
                'nullable',
                'boolean',
            ],
            'notification_emails' => [
                'nullable',
                'array',
            ],
            'notification_emails.*' => [
                'email',
            ],
            'metadata' => [
                'nullable',
                'array',
            ],
        ];
    }
}
