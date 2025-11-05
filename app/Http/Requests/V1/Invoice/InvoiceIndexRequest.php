<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\Invoice;

use App\Http\Requests\V1\BaseFormRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class InvoiceIndexRequest extends BaseFormRequest
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
                'in:all,draft,sent,paid,overdue,cancelled',
            ],
            'client_id' => [
                'nullable',
                'string',
                'uuid',
            ],
            'search' => [
                'nullable',
                'string',
                'max:255',
            ],
            'start_date' => [
                'nullable',
                'date',
            ],
            'end_date' => [
                'nullable',
                'date',
                'after_or_equal:start_date',
            ],
        ];
    }
}
