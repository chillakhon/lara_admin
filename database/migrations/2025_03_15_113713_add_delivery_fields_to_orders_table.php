<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDeliveryFieldsToOrdersTable extends Migration
{
    /**
     * Запуск миграции.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'delivery_date')) {
                $table->dateTime('delivery_date')->nullable();  // Добавляем поле для даты доставки
            }
            if (!Schema::hasColumn('orders', 'delivery_method')) {
                $table->json('delivery_method')->nullable();    // Добавляем поле для метода доставки
            }
        });
    }

    /**
     * Откат миграции.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'delivery_date')) {
                $table->dropColumn('delivery_date');  // Убираем поле даты доставки
            }
            if (Schema::hasColumn('orders', 'delivery_method')) {
                $table->dropColumn('delivery_method');  // Убираем поле метода доставки
            }
        });
    }
}
