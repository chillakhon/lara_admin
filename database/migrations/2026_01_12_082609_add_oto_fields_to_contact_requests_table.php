<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contact_requests', function (Blueprint $table) {
            // Связь с OTO баннером
            $table->foreignId('oto_banner_id')
                ->nullable()
                ->after('client_id')
                ->constrained('oto_banners')
                ->onDelete('set null');

            // Менеджер, ответственный за заявку
            $table->foreignId('manager_id')
                ->nullable()
                ->after('oto_banner_id')
                ->constrained('users')
                ->onDelete('set null');

            // Индексы для быстрого поиска
            $table->index('oto_banner_id');
            $table->index('manager_id');
        });
    }

    public function down(): void
    {
        Schema::table('contact_requests', function (Blueprint $table) {
            $table->dropForeign(['oto_banner_id']);
            $table->dropForeign(['manager_id']);
            $table->dropColumn(['oto_banner_id', 'manager_id']);
        });
    }
};
