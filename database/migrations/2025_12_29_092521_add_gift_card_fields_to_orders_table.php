<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('gift_card_id')->nullable()->after('promo_code_id')->constrained('gift_cards')->onDelete('set null');
            $table->decimal('gift_card_amount', 10, 2)->default(0)->after('gift_card_id'); // Сколько списали с карты
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['gift_card_id']);
            $table->dropColumn(['gift_card_id', 'gift_card_amount']);
        });
    }
};
