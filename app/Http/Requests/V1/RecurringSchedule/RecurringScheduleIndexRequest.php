<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\RecurringSchedule;

use App\Http\Requests\V1\BaseFormRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class RecurringScheduleIndexRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<string|ValidationRule>>
     */
    public function rules(): array
    {
        return [
            'status' => [
                'nullable',
                'string',
                'in:all,active,paused,completed',
            ],
            'frequency' => [
                'nullable',
                'string',
                'in:daily,weekly,biweekly,monthly,quarterly,biannually,annually',
            ],
            'client_id' => [
                'nullable',
                'string',
                'uuid',
            ],
        ];
    }
}
