<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('price', 10, 2)->nullable(); // Цена по умолчанию (если нет вариантов)
            $table->decimal('cost_price', 10, 2)->nullable(); // Себестоимость
            $table->string('currency', 3)->default('RUB');
            $table->integer('stock_quantity')->default(0); // Количество на складе (если нет вариантов)
            $table->integer('min_order_quantity')->default(1); // Минимальный заказ
            $table->integer('max_order_quantity')->nullable(); // Максимальный заказ (null = без ограничений)
            $table->boolean('is_featured')->default(false); // Отображать как "избранный товар"
            $table->boolean('is_new')->default(false); // Новый товар?
            $table->decimal('discount_price', 10, 2)->nullable(); // Цена до скидки
            $table->string('sku')->unique()->nullable(); // Артикул
            $table->string('barcode')->unique()->nullable(); // Штрих-код
            $table->decimal('weight', 8, 3)->nullable(); // Вес в кг
            $table->decimal('length', 8, 2)->nullable(); // Длина в см
            $table->decimal('width', 8, 2)->nullable(); // Ширина в см
            $table->decimal('height', 8, 2)->nullable(); // Высота в см
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            //
        });
    }
};
