<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('promo_code_product', function (Blueprint $table) {
            // Удаляем внешние ключи
            $table->dropForeign(['promo_code_id']);
            $table->dropForeign(['product_id']);
            $table->dropForeign(['product_variant_id']);

            // Теперь можно удалить индекс
            $table->dropUnique('promo_product_variant_unique');
        });
    }

    public function down(): void
    {
        Schema::table('promo_code_product', function (Blueprint $table) {
            // Восстанавливаем внешний ключ
            $table->foreign('promo_code_id')->references('id')->on('promo_codes')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->foreign('product_variant_id')->references('id')->on('product_variants')->cascadeOnDelete();

            // Восстанавливаем уникальный индекс
            $table->unique(['promo_code_id', 'product_id', 'product_variant_id'], 'promo_product_variant_unique');
        });
    }
};
