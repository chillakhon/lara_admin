<?php

use DefStudio\Telegraph\Models\TelegraphBot;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Artisan::command('bot_settings', function () {
    $bot = TelegraphBot::first();

    dd($bot->registerCommands([
        "help" => "Что умеет этот бот",
        "start" => "Начать использовать наш бот",
        "orders" => "Ожидающие заказы",
        "reset" => "Сбросить данные и начать заново"
    ])->send());
});