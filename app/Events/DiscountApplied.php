<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DiscountApplied
{
    use Dispatchable, SerializesModels;

    public $orderDiscount;

    public function __construct($orderDiscount)
    {
        $this->orderDiscount = $orderDiscount;
    }
} 