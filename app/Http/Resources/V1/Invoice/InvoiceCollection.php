<?php

declare(strict_types=1);

namespace App\Http\Resources\V1\Invoice;

use Illuminate\Http\Resources\Json\ResourceCollection;

class InvoiceCollection extends ResourceCollection
{
    /**
     * The resource that this resource collects.
     *
     * @var string
     */
    public $collects = InvoiceResource::class;
}
