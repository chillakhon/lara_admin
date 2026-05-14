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
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('total_amount_original', 2)->nullable()->after('total_amount');
            $table->decimal('total_promo_discount', 2)->nullable()->after('discount_amount');
            $table->decimal('total_items_discount', 2)->nullable()->after('total_promo_discount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'total_amount_original',
                'total_promo_discount',
                'total_items_discount'
            ]);
        });
    }
};
