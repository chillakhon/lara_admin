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
            if (!Schema::hasColumn('products', 'old_price')) {
                $table->decimal('old_price', 10, 2)->nullable()->after('price');
            }
            if (!Schema::hasColumn('products', 'barcode')) {
                $table->string('barcode')->nullable()->after('type');
            }
        });

        Schema::table('product_variants', function (Blueprint $table) {
            if (!Schema::hasColumn('product_variants', 'old_price')) {
                $table->decimal('old_price', 10, 2)->nullable()->after('price');
            }
            if (!Schema::hasColumn('product_variants', 'cost_price')) {
                $table->decimal('cost_price', 10, 2)->nullable()->after('old_price');
            }
            if (!Schema::hasColumn('product_variants', 'barcode')) {
                $table->string('barcode')->nullable()->after('sku');
            }
        });

        Schema::table('inventory_balances', function (Blueprint $table) {
            $table->decimal('average_price', 10, 3)->nullable()->change();
            $table->unsignedBigInteger('unit_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropColumns('products', ['old_price', 'barcode']);
        Schema::dropColumns('product_variants', ['old_price', 'cost_price', 'barcode']);
        Schema::table('inventory_balances', function (Blueprint $table) {
            $table->decimal('average_price', 10, 2)->change();
            $table->unsignedBigInteger('unit_id')->change();
        });
    }
};
