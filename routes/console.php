<?php

use App\Console\Commands\CheckDiscountsValidity;
use DefStudio\Telegraph\Models\TelegraphBot;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Artisan::command('discounts:check-validity', function () {
    $check_discount_validity = new CheckDiscountsValidity();
    $check_discount_validity->handle();
})->purpose('Activate and deactivate discounts')->everyMinute();

Artisan::command('bot_settings', function () {
    $bot = TelegraphBot::first();

    dd($bot->registerCommands([
        "help" => "Что умеет этот бот",
        "start" => "Начать использовать наш бот",
        "orders" => "Заказы",
        "reset" => "Сбросить данные и начать заново"
    ])->send());
});
