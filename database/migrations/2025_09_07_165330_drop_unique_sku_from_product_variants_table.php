<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            // Удаляем уникальный индекс по sku
            $table->dropUnique('product_variants_sku_unique');
        });
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            // Восстанавливаем уникальный индекс по sku
            $table->unique('sku', 'product_variants_sku_unique');
        });
    }
};
