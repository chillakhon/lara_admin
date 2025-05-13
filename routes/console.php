<?php

use DefStudio\Telegraph\Models\TelegraphBot;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Artisan::command('bot_settings', function () {


    $bot = TelegraphBot::find(1);

    dd($bot->registerCommands([
        'hello' => "Говорит привет",
        "help" => "Что умеет этот бот",
        "actions" => "Различные действия"
    ])->send());
});