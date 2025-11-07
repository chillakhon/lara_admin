<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            // Если колонка существует - изменяем
            // Если нет - добавляем

            $table->timestamp('last_message_at')
                ->nullable()
                ->default(null)
                ->change(); // ← ВАЖНО: change() для изменения существующей
        });
    }

    public function down(): void
    {

    }
};
