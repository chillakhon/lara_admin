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
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cart_id')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('product_variant_id')->nullable();
            $table->integer('quantity');


            $table->foreign('cart_id', 'fk_cart_id')->on('cart')
                ->references('id')->onDelete('set null')->onUpdate('cascade');

            $table->foreign('product_id', 'fk_product_id')->on('products')
                ->references('id')->onDelete('set null')->onUpdate('cascade');

            $table->foreign('product_variant_id', 'fk_product_variants')->on('product_variants')
                ->references('id')->onDelete('set null')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};
