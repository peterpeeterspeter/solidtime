<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\Invoice;

use App\Http\Requests\V1\BaseFormRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class InvoiceUpdateRequest extends BaseFormRequest
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
            'invoice_number' => [
                'sometimes',
                'string',
                'max:255',
            ],
            'status' => [
                'sometimes',
                'string',
                'in:draft,sent,paid,overdue,cancelled',
            ],
            'issue_date' => [
                'sometimes',
                'date',
            ],
            'due_date' => [
                'sometimes',
                'date',
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
            'notes' => [
                'nullable',
                'string',
            ],
            'terms' => [
                'nullable',
                'string',
            ],
            'payment_method' => [
                'nullable',
                'string',
                'max:255',
            ],
            'payment_instructions' => [
                'nullable',
                'string',
            ],
            'metadata' => [
                'sometimes',
                'array',
            ],
        ];
    }
}
