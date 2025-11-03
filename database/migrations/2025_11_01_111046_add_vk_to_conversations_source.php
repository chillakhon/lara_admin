<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Query\Expression;

return new class extends Migration {
    public function up(): void
    {
        // Меняем enum, добавляя 'vk'
        DB::statement("ALTER TABLE conversations MODIFY source ENUM('telegram', 'whatsapp', 'web_chat', 'vk') NOT NULL");
    }

    public function down(): void
    {
        // Возвращаем обратно без 'vk'
        DB::statement("ALTER TABLE conversations MODIFY source ENUM('telegram', 'whatsapp', 'web_chat') NOT NULL");
    }
};
