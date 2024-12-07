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
        Schema::create('client_levels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('threshold', 10, 2);
            $table->enum('calculation_type', ['order_count', 'order_sum']);
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_levels');
    }
};
