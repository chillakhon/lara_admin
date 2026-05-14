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
            if (!Schema::hasColumn('products', 'price')) {
                $table->decimal('price', 10, 2)->nullable();
            }
            if (!Schema::hasColumn('products', 'cost_price')) {
                $table->decimal('cost_price', 10, 2)->nullable();
            }
            if (!Schema::hasColumn('products', 'currency')) {
                $table->string('currency', 3)->default('RUB');
            }
            if (!Schema::hasColumn('products', 'stock_quantity')) {
                $table->integer('stock_quantity')->default(0);
            }
            if (!Schema::hasColumn('products', 'min_order_quantity')) {
                $table->integer('min_order_quantity')->default(1);
            }
            if (!Schema::hasColumn('products', 'max_order_quantity')) {
                $table->integer('max_order_quantity')->nullable();
            }
            if (!Schema::hasColumn('products', 'is_featured')) {
                $table->boolean('is_featured')->default(false);
            }
            if (!Schema::hasColumn('products', 'is_new')) {
                $table->boolean('is_new')->default(false);
            }
            if (!Schema::hasColumn('products', 'discount_price')) {
                $table->decimal('discount_price', 10, 2)->nullable();
            }
            if (!Schema::hasColumn('products', 'sku')) {
                $table->string('sku')->unique()->nullable();
            }
            if (!Schema::hasColumn('products', 'barcode')) {
                $table->string('barcode')->unique()->nullable();
            }
            if (!Schema::hasColumn('products', 'weight')) {
                $table->decimal('weight', 8, 3)->nullable();
            }
            if (!Schema::hasColumn('products', 'length')) {
                $table->decimal('length', 8, 2)->nullable();
            }
            if (!Schema::hasColumn('products', 'width')) {
                $table->decimal('width', 8, 2)->nullable();
            }
            if (!Schema::hasColumn('products', 'height')) {
                $table->decimal('height', 8, 2)->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'price')) {
                $table->dropColumn('price');
            }
            if (Schema::hasColumn('products', 'cost_price')) {
                $table->dropColumn('cost_price');
            }
            if (Schema::hasColumn('products', 'currency')) {
                $table->dropColumn('currency');
            }
            if (Schema::hasColumn('products', 'stock_quantity')) {
                $table->dropColumn('stock_quantity');
            }
            if (Schema::hasColumn('products', 'min_order_quantity')) {
                $table->dropColumn('min_order_quantity');
            }
            if (Schema::hasColumn('products', 'max_order_quantity')) {
                $table->dropColumn('max_order_quantity');
            }
            if (Schema::hasColumn('products', 'is_featured')) {
                $table->dropColumn('is_featured');
            }
            if (Schema::hasColumn('products', 'is_new')) {
                $table->dropColumn('is_new');
            }
            if (Schema::hasColumn('products', 'discount_price')) {
                $table->dropColumn('discount_price');
            }
            if (Schema::hasColumn('products', 'sku')) {
                $table->dropColumn('sku');
            }
            if (Schema::hasColumn('products', 'barcode')) {
                $table->dropColumn('barcode');
            }
            if (Schema::hasColumn('products', 'weight')) {
                $table->dropColumn('weight');
            }
            if (Schema::hasColumn('products', 'length')) {
                $table->dropColumn('length');
            }
            if (Schema::hasColumn('products', 'width')) {
                $table->dropColumn('width');
            }
            if (Schema::hasColumn('products', 'height')) {
                $table->dropColumn('height');
            }
        });
    }
};
