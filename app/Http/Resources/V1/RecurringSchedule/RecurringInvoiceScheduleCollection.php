<?php

declare(strict_types=1);

namespace App\Http\Resources\V1\RecurringSchedule;

use Illuminate\Http\Resources\Json\ResourceCollection;

class RecurringInvoiceScheduleCollection extends ResourceCollection
{
    /**
     * The resource that this resource collects.
     *
     * @var string
     */
    public $collects = RecurringInvoiceScheduleResource::class;
}
