<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Таблица инвентаризаций
        Schema::create('inventory_audits', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique(); // Номер инвентаризации
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('completed_by')->nullable()->constrained('users');
            $table->enum('status', ['draft', 'in_progress', 'completed', 'cancelled'])
                ->default('draft');
            $table->text('notes')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Таблица позиций инвентаризации
        Schema::create('inventory_audit_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_audit_id')->constrained()->onDelete('cascade');
            $table->morphs('item'); // Для связи с Material или Product
            $table->foreignId('unit_id')->constrained();
            $table->decimal('expected_quantity', 10, 3);
            $table->decimal('actual_quantity', 10, 3)->nullable();
            $table->decimal('difference', 10, 3)->nullable();
            $table->decimal('cost_per_unit', 10, 2);
            $table->decimal('difference_cost', 10, 2)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('counted_by')->nullable()->constrained('users');
            $table->timestamp('counted_at')->nullable();
            $table->timestamps();
        });

        // Таблица истории изменений позиций
        Schema::create('inventory_audit_item_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_audit_item_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained();
            $table->decimal('old_quantity', 10, 3)->nullable();
            $table->decimal('new_quantity', 10, 3);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_audit_item_histories');
        Schema::dropIfExists('inventory_audit_items');
        Schema::dropIfExists('inventory_audits');
    }
}; 