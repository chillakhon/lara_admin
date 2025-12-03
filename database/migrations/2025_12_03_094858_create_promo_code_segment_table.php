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
        Schema::create('promo_code_segment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promo_code_id')->constrained('promo_codes')->onDelete('cascade');
            $table->foreignId('segment_id')->constrained('segments')->onDelete('cascade');
            $table->boolean('auto_apply')->default(true)->comment('Автоматически применять промокод');
            $table->timestamps();

            // Уникальный индекс - промокод может быть привязан к сегменту только один раз
            $table->unique(['promo_code_id', 'segment_id']);

            // Индексы для быстрого поиска
            $table->index('promo_code_id');
            $table->index('segment_id');
            $table->index('auto_apply');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promo_code_segment');
    }
};
