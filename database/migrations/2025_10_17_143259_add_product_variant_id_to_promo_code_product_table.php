<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('promo_code_product', function (Blueprint $table) {
            if (!Schema::hasColumn('promo_code_product', 'product_variant_id')) {
                $table->foreignId('product_variant_id')
                    ->nullable()
                    ->after('product_id')
                    ->constrained('product_variants')
                    ->cascadeOnDelete();
            }

            if (!Schema::hasColumn('promo_code_product', 'promo_product_variant_unique')) {
                $table->unique(['promo_code_id', 'product_id', 'product_variant_id'], 'promo_product_variant_unique');
            }
        });

    }

    public function down(): void
    {
        Schema::table('promo_code_product', function (Blueprint $table) {
            $table->dropUnique('promo_product_variant_unique');
            $table->dropConstrainedForeignId('product_variant_id');
        });
    }
};
