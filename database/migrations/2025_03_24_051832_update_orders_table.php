<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            // while running migration, it's not creating because of that it
            // was not created in table before,
            // that's why Im checking that once again
            if (!Schema::hasColumn('orders', 'delivery_target_id')) {
                $table->unsignedBigInteger('delivery_target_id');
            }

            // Обновляем столбец, делаем его nullable
            $table->unsignedBigInteger('delivery_target_id')->nullable()->change();

            // Добавляем связь с таблицей delivery_methods, если её ещё нет
            if (!Schema::hasColumn('orders', 'delivery_method_id')) {
                $table->foreign('delivery_method_id')->references('id')->on('delivery_methods')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            // Убираем связь с delivery_methods
            $table->dropForeign(['delivery_method_id']);
            // Восстанавливаем столбец без nullable
            $table->unsignedBigInteger('delivery_target_id')->nullable(false)->change();
        });
    }
}
