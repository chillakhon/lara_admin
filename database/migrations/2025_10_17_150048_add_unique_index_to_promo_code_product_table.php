<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('promo_code_product', function (Blueprint $table) {
            $table->unique(['promo_code_id', 'product_id', 'product_variant_id'], 'promo_product_variant_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('promo_code_product', function (Blueprint $table) {
            $table->dropUnique('promo_product_variant_unique');
        });
    }
};
