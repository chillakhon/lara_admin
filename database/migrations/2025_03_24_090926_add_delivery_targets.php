<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddDeliveryTargets extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('delivery_targets')->insert([
            [
                'name' => 'Пункт выдачи СДЭК',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Пункт самовывоза Boxberry',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Курьером Boxberry',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Доставка в отделение Почты России',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Доставка курьером Почты России',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // ... добавь другие пункты выдачи ...
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('delivery_targets')->truncate(); // Очищаем таблицу при откате миграции
    }
}
