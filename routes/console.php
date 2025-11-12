<?php

use App\Console\Commands\SyncEmailMessages;
use App\Console\Commands\CheckDiscountsValidity;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('discounts:check-validity', function () {
    $check_discount_validity = new CheckDiscountsValidity();
    $check_discount_validity->handle();
})->purpose('Activate and deactivate discounts')->everyFiveMinutes();


Schedule::command('email:sync')->everyMinute();
