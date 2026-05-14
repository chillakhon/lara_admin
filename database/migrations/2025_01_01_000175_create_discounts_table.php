<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('discounts')) {
            Schema::create('discounts', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('type')->default('percentage'); // percentage, fixed, special_price
                $table->decimal('value', 10, 2); // процент или фиксированная сумма
                $table->boolean('is_active')->default(true);
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('ends_at')->nullable();
                $table->integer('priority')->default(0); // приоритет применения скидки
                $table->json('conditions')->nullable(); // условия применения скидки
                $table->string('discount_type')->default('specific'); // specific, category, all
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('discountables')) {
            Schema::create('discountables', function (Blueprint $table) {
                $table->id();
                $table->foreignId('discount_id')->constrained()->onDelete('cascade');
                $table->morphs('discountable'); // для products или product_variants
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('discountables');
        Schema::dropIfExists('discounts');
    }
};