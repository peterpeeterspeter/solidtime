<?php

declare(strict_types=1);

namespace App\Http\Resources\V1\PaymentGateway;

use Illuminate\Http\Resources\Json\ResourceCollection;

class PaymentGatewayConnectionCollection extends ResourceCollection
{
    /**
     * The resource that this resource collects.
     *
     * @var string
     */
    public $collects = PaymentGatewayConnectionResource::class;
}
