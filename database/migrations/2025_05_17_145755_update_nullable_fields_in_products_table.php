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
            // Making columns nullable
            $table->enum('type', ['simple', 'manufactured', 'composite', 'material'])->nullable()->default('simple')->change();
            $table->unsignedBigInteger('default_unit_id')->nullable()->change();
            $table->boolean('is_active')->nullable()->default(1)->change();
            $table->boolean('has_variants')->nullable()->default(0)->change();
            $table->boolean('allow_preorder')->nullable()->default(0)->change();
            $table->smallInteger('after_purchase_processing_time')->nullable()->default(0)->change();
            $table->decimal('price', 10, 2)->nullable()->change();
            $table->decimal('old_price', 10, 2)->nullable()->change();
            $table->decimal('cost_price', 10, 2)->nullable()->change();
            $table->string('currency', 3)->nullable()->default('RUB')->change();
            $table->integer('stock_quantity')->nullable()->default(0)->change();
            $table->integer('min_order_quantity')->nullable()->default(1)->change();
            $table->integer('max_order_quantity')->nullable()->change();
            $table->boolean('is_featured')->nullable()->default(0)->change();
            $table->boolean('is_new')->nullable()->default(0)->change();
            $table->decimal('discount_price', 10, 2)->nullable()->change();
            $table->string('sku')->nullable()->change();
            $table->string('barcode')->nullable()->change();
            $table->decimal('weight', 8, 3)->nullable()->change();
            $table->decimal('length', 8, 2)->nullable()->change();
            $table->decimal('width', 8, 2)->nullable()->change();
            $table->decimal('height', 8, 2)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
