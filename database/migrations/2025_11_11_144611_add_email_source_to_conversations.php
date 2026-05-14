<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement("ALTER TABLE conversations MODIFY source ENUM('telegram', 'whatsapp', 'web_chat', 'vk', 'email') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE conversations MODIFY source ENUM('telegram', 'whatsapp', 'web_chat', 'vk') NOT NULL");
    }
};
