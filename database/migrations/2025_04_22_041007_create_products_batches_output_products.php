<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('production_batches_output_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('production_batch_id');
            // $table->string('production_batch_number')->nullable();
            $table->morphs('output');
            $table->decimal('qty', 10, 3);
            $table->timestamps();
            $table->foreign('production_batch_id')
                ->references('id')
                ->on('production_batches')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_batches_output_products');
    }
};
