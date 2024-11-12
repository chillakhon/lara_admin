<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('component_consumptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_batch_id')->constrained()->onDelete('cascade');
            $table->morphs('component');
            $table->foreignId('inventory_batch_id')->constrained();
            $table->decimal('quantity', 10, 3);
            $table->decimal('price_per_unit', 10, 2);
            $table->foreignId('unit_id')->constrained();
            $table->decimal('waste_quantity', 10, 3)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_component_consumptions');
    }
};
