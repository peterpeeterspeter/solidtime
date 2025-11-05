<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\PushSubscription;

use Illuminate\Foundation\Http\FormRequest;

class PushSubscriptionStoreRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'endpoint' => [
                'required',
                'string',
                'url',
                'max:500',
            ],
            'keys' => [
                'required',
                'array',
            ],
            'keys.p256dh' => [
                'required',
                'string',
            ],
            'keys.auth' => [
                'required',
                'string',
            ],
        ];
    }
}
