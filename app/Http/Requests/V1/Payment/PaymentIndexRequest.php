<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\Payment;

use App\Http\Requests\V1\BaseFormRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class PaymentIndexRequest extends BaseFormRequest
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
                'in:all,pending,processing,completed,failed,refunded,partially_refunded',
            ],
            'invoice_id' => [
                'nullable',
                'string',
                'uuid',
            ],
            'gateway' => [
                'nullable',
                'string',
                'in:stripe,paypal',
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
