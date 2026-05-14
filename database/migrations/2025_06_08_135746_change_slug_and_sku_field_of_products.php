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
        Schema::table('products', function (Blueprint $table) {
            $table->string('slug')->nullable()->change();
            $table->string('sku')->nullable()->change();
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->string('sku')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('slug')->change();
            $table->string('sku')->change();
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->string('sku')->change();
        });
    }
};
