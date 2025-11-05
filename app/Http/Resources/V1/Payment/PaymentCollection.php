<?php

declare(strict_types=1);

namespace App\Http\Resources\V1\Payment;

use Illuminate\Http\Resources\Json\ResourceCollection;

class PaymentCollection extends ResourceCollection
{
    /**
     * The resource that this resource collects.
     *
     * @var string
     */
    public $collects = PaymentResource::class;
}
