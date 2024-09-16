<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variant_color_option_value', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained()->onDelete('cascade');
            $table->foreignId('color_option_value_id')->constrained()->onDelete('cascade');
            $table->foreignId('color_option_id')->constrained()->onDelete('cascade');
            $table->unique(['product_variant_id', 'color_option_id'], 'unique_variant_color_option');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variant_color_option_value');
    }
};
