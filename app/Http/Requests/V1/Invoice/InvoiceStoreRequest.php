<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\Invoice;

use App\Http\Requests\V1\BaseFormRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class InvoiceStoreRequest extends BaseFormRequest
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
            'invoice_number' => [
                'nullable',
                'string',
                'max:255',
            ],
            'status' => [
                'nullable',
                'string',
                'in:draft,sent,paid,overdue,cancelled',
            ],
            'issue_date' => [
                'required',
                'date',
            ],
            'due_date' => [
                'required',
                'date',
                'after_or_equal:issue_date',
            ],
            'from_details' => [
                'required',
                'array',
            ],
            'from_details.name' => [
                'required',
                'string',
                'max:255',
            ],
            'from_details.email' => [
                'nullable',
                'email',
                'max:255',
            ],
            'from_details.address' => [
                'nullable',
                'string',
            ],
            'to_details' => [
                'required',
                'array',
            ],
            'to_details.name' => [
                'required',
                'string',
                'max:255',
            ],
            'to_details.email' => [
                'nullable',
                'email',
                'max:255',
            ],
            'to_details.address' => [
                'nullable',
                'string',
            ],
            'line_items' => [
                'required',
                'array',
                'min:1',
            ],
            'line_items.*.description' => [
                'required',
                'string',
            ],
            'line_items.*.quantity' => [
                'required',
                'numeric',
                'min:0',
            ],
            'line_items.*.unit_price' => [
                'required',
                'numeric',
                'min:0',
            ],
            'line_items.*.amount' => [
                'required',
                'numeric',
                'min:0',
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
                'nullable',
                'array',
            ],
        ];
    }
}
