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
        Schema::table('cart_items', function (Blueprint $table) {
            $table->decimal('price', 10, 2)
                ->after('quantity')
                ->nullable();
            $table->decimal('price_original', 10, 2)
                ->after('price')
                ->nullable();
            $table->decimal('total_discount', 10, 2)
                ->after('price_original')
                ->nullable();
            $table->decimal('total', 10, 2)
                ->after('total_discount')
                ->nullable();
            $table->decimal('total_original', 10, 2)
                ->after('total')
                ->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropColumn(['price', 'price_original', 'total_discount', 'total', 'total_original']);
        });
    }
};
