<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Unique индекс на (source, external_id) — защита от дублирования диалогов
        Schema::table('conversations', function (Blueprint $table) {
            $table->unique(['source', 'external_id'], 'conversations_source_external_id_unique');
        });

        // messages.content — делаем nullable, т.к. сообщение может состоять только из вложений
        Schema::table('messages', function (Blueprint $table) {
            $table->text('content')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropUnique('conversations_source_external_id_unique');
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->text('content')->nullable(false)->change();
        });
    }
};
