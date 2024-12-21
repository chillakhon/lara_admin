<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class Delivery extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'delivery';
    }
} 