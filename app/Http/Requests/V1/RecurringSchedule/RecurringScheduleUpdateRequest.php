<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\RecurringSchedule;

use App\Http\Requests\V1\BaseFormRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class RecurringScheduleUpdateRequest extends BaseFormRequest
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
                'sometimes',
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
                'sometimes',
                'string',
                'max:255',
            ],
            'frequency' => [
                'sometimes',
                'string',
                'in:daily,weekly,biweekly,monthly,quarterly,biannually,annually',
            ],
            'interval' => [
                'sometimes',
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
                'sometimes',
                'date',
            ],
            'end_date' => [
                'nullable',
                'date',
            ],
            'max_occurrences' => [
                'nullable',
                'integer',
                'min:1',
            ],
            'from_details' => [
                'sometimes',
                'array',
            ],
            'to_details' => [
                'sometimes',
                'array',
            ],
            'line_items' => [
                'sometimes',
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
                'sometimes',
                'numeric',
                'min:0',
            ],
            'tax_rate' => [
                'sometimes',
                'numeric',
                'min:0',
                'max:100',
            ],
            'tax_amount' => [
                'sometimes',
                'numeric',
                'min:0',
            ],
            'discount_amount' => [
                'sometimes',
                'numeric',
                'min:0',
            ],
            'total' => [
                'sometimes',
                'numeric',
                'min:0',
            ],
            'currency' => [
                'sometimes',
                'string',
                'size:3',
            ],
            'due_days' => [
                'sometimes',
                'integer',
                'min:0',
            ],
            'due_date_type' => [
                'sometimes',
                'string',
                'in:days_after_generation,specific_day',
            ],
            'auto_send' => [
                'sometimes',
                'boolean',
            ],
            'auto_charge' => [
                'sometimes',
                'boolean',
            ],
            'include_time_entries' => [
                'sometimes',
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
                'sometimes',
                'boolean',
            ],
            'notification_emails' => [
                'sometimes',
                'array',
            ],
            'notification_emails.*' => [
                'email',
            ],
            'metadata' => [
                'sometimes',
                'array',
            ],
        ];
    }
}
