<?php

use App\Models\Material;
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
        Schema::create('material_conversions', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Material::class)->constrained()->onDelete('cascade');
            $table->string('from_unit', 50);
            $table->string('to_unit', 50);
            $table->decimal('conversion_factor', 10, 3);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('material_conversions');
    }
};
