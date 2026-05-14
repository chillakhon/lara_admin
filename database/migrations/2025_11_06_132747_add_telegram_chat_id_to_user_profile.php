<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            // Если их ещё нет
            if (!Schema::hasColumn('user_profiles', 'telegram_chat_id')) {
                $table->unsignedBigInteger('telegram_chat_id')->nullable()->after('telegram_user_id');
                $table->index('telegram_chat_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropColumn(['telegram_chat_id']);
        });
    }
};
