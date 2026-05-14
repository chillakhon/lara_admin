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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->enum('type', ['simple', 'manufactured', 'composite'])
                ->default('simple');
            $table->foreignId('default_unit_id')
                ->nullable()
                ->constrained('units');
            $table->boolean('is_active')->default(true);
            $table->boolean('has_variants')->default(false);
            $table->boolean('allow_preorder')->default(false);
            $table->smallInteger('after_purchase_processing_time')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
