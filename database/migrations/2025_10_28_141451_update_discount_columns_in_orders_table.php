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
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('total_promo_discount', 10, 2)->nullable()->change();
            $table->decimal('total_items_discount', 10, 2)->nullable()->change();
            $table->decimal('total_amount_original', 10, 2)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('total_promo_discount', 2, 2)->change()->nullable();
            $table->decimal('total_items_discount', 2, 2)->change()->nullable();
            $table->decimal('total_amount_original', 2, 2)->change()->nullable();
        });
    }
};
