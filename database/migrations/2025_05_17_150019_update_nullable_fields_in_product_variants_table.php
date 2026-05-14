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
        Schema::table('product_variants', function (Blueprint $table) {
            $table->string('barcode')->nullable()->change();
            $table->decimal('old_price', 10, 2)->nullable()->change();
            $table->decimal('cost_price', 10, 2)->nullable()->change();
            $table->integer('stock')->nullable()->default(0)->change();
            $table->decimal('additional_cost', 10, 2)->nullable()->default(0.00)->change();
            $table->enum('type', ['simple', 'manufactured', 'composite', 'material'])->nullable()->default('simple')->change();
            $table->unsignedBigInteger('unit_id')->nullable()->change();
            $table->boolean('is_active')->nullable()->default(1)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
