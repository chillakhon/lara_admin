<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_method_id')->constrained()->onDelete('cascade');
            $table->foreignId('delivery_zone_id')->constrained()->onDelete('cascade');
            $table->decimal('min_weight', 10, 3)->nullable();
            $table->decimal('max_weight', 10, 3)->nullable();
            $table->decimal('min_total', 10, 2)->nullable();
            $table->decimal('max_total', 10, 2)->nullable();
            $table->decimal('price', 10, 2);
            $table->integer('estimated_days')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_rates');
    }
}; 