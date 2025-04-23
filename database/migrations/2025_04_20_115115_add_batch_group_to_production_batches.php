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
        Schema::table('production_batches', function (Blueprint $table) {
            $table->string('batch_number')->nullable()->change();
            $table->unsignedBigInteger('recipe_id')->nullable()->change();
            $table->unsignedBigInteger('product_variant_id')->nullable()->change();
            $table->decimal('planned_quantity', 10, 3)->nullable()->change();
            $table->enum('status', ['planned', 'pending', 'in_progress', 'completed', 'cancelled', 'failed'])->default('planned')->nullable()->change();
            $table->decimal('additional_costs', 10, 2)->default(0.00)->nullable()->change();
            $table->unsignedBigInteger('created_by')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('production_batches', function (Blueprint $table) {
            $table->string('batch_number')->nullable(false)->change();
            $table->unsignedBigInteger('recipe_id')->nullable(false)->change();
            $table->unsignedBigInteger('product_variant_id')->nullable(false)->change();
            $table->decimal('planned_quantity', 10, 3)->nullable(false)->change();
            $table->enum('status', ['planned', 'pending', 'in_progress', 'completed', 'cancelled', 'failed'])->default('planned')->nullable(false)->change();
            $table->decimal('additional_costs', 10, 2)->default(0.00)->nullable(false)->change();
            $table->unsignedBigInteger('created_by')->nullable(false)->change();
        });
    }
};
