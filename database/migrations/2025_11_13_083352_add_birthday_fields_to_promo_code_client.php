<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('promo_code_client', function (Blueprint $table) {
            $table->boolean('birthday_discount')->default(false)->after('client_id');
            $table->timestamp('notified_at')->nullable()->after('birthday_discount');
            $table->boolean('reminder_sent')->default(false)->after('notified_at');
        });
    }

    public function down(): void
    {
        Schema::table('promo_code_client', function (Blueprint $table) {
            $table->dropColumn(['birthday_discount', 'notified_at', 'reminder_sent']);
        });
    }
};
