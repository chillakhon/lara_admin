<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Категории затрат
        Schema::create('cost_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type'); // labor, overhead, management, etc.
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Базовые ставки затрат для рецептов
        Schema::create('recipe_cost_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_id')->constrained()->onDelete('cascade');
            $table->foreignId('cost_category_id')->constrained()->onDelete('cascade');
            $table->decimal('rate_per_unit', 10, 2); // Ставка за единицу продукции
            $table->decimal('fixed_rate', 10, 2)->default(0); // Фиксированная ставка на партию
            $table->timestamps();
        });

        // Фактические затраты производственных партий
        Schema::create('production_batch_costs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_batch_id')->constrained()->onDelete('cascade');
            $table->foreignId('cost_category_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_batch_costs');
        Schema::dropIfExists('recipe_cost_rates');
        Schema::dropIfExists('cost_categories');
    }
};
