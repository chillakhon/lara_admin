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
        Schema::create('client_segment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->foreignId('segment_id')->constrained('segments')->onDelete('cascade');
            $table->timestamp('added_at')->useCurrent()->comment('Когда клиент попал в сегмент');
            $table->timestamps();

            // Уникальный индекс - клиент может быть в сегменте только один раз
            $table->unique(['client_id', 'segment_id']);

            // Индексы для быстрого поиска
            $table->index('client_id');
            $table->index('segment_id');
            $table->index('added_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_segment');
    }
};
