<?php

use App\Models\Color;
use App\Models\ColorOption;
use App\Models\Product;
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
        Schema::create('color_option_values', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Color::class)->constrained();
            $table->foreignIdFor(Product::class)->constrained();
            $table->foreignIdFor(ColorOption::class)->constrained();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('color_option_values');
    }
};
