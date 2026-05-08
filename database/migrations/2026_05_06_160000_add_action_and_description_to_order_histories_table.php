<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Добавляет в order_histories поля:
     * - action      — тип события (created/updated/deleted/item_added/item_removed)
     * - description — человекочитаемое описание события
     *
     * Старые поля status/payment_status/comment не трогаем — они продолжают
     * использоваться в Order::updatePaymentStatus() и ShipmentObserver.
     */
    public function up(): void
    {
        Schema::table('order_histories', function (Blueprint $table) {
            $table->string('action', 32)->nullable()->after('user_id');
            $table->text('description')->nullable()->after('action');
        });
    }

    public function down(): void
    {
        Schema::table('order_histories', function (Blueprint $table) {
            $table->dropColumn(['action', 'description']);
        });
    }
};
