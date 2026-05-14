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
        Schema::create('segments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('conditions')->nullable()->comment('Условия фильтрации клиентов');
            $table->boolean('is_active')->default(true);
            $table->enum('recalculate_frequency', ['on_view', 'manual'])->default('on_view');
            $table->timestamp('last_recalculated_at')->nullable();
            $table->timestamps();

            $table->index('is_active');
            $table->index('last_recalculated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('segments');
    }
};
