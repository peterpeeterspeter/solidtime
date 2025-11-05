<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\PaymentGateway;

use App\Http\Requests\V1\BaseFormRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class PaymentGatewayAuthorizationUrlRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<string|ValidationRule>>
     */
    public function rules(): array
    {
        return [
            'gateway' => [
                'required',
                'string',
                'in:stripe,paypal',
            ],
            'redirect_uri' => [
                'required',
                'string',
                'url',
            ],
        ];
    }
}
