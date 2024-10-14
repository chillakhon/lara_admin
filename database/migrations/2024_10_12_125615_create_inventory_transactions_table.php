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
        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->id();
            $table->morphs('item');
            $table->enum('type', ['incoming', 'outgoing', 'adjustment']);
            $table->decimal('quantity', 10, 3);
            $table->decimal('price_per_unit', 10, 2);
            $table->foreignId('unit_id')->constrained()->onDelete('restrict');
            $table->unsignedBigInteger('batch_id')->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();

            $table->foreign('batch_id')->references('id')->on('inventory_batches')->onDelete('set null');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_transactions');
    }
};
