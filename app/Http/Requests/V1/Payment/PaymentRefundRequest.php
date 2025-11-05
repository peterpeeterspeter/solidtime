<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\Payment;

use App\Http\Requests\V1\BaseFormRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class PaymentRefundRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<string|ValidationRule>>
     */
    public function rules(): array
    {
        return [
            'amount' => [
                'nullable',
                'numeric',
                'min:0.01',
            ],
            'reason' => [
                'nullable',
                'string',
                'max:500',
            ],
        ];
    }
}
