<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Добавляет поля получателя в адрес доставки заказа.
     * Имя/Фамилия/Телефон — обязательны на уровне приложения,
     * Отчество — опционально. На уровне БД оставляем все nullable,
     * чтобы не сломать существующие записи order_addresses.
     */
    public function up(): void
    {
        Schema::table('order_addresses', function (Blueprint $table) {
            $table->string('recipient_first_name')->nullable()->after('order_id');
            $table->string('recipient_last_name')->nullable()->after('recipient_first_name');
            $table->string('recipient_middle_name')->nullable()->after('recipient_last_name');
            $table->string('recipient_phone', 32)->nullable()->after('recipient_middle_name');
        });
    }

    public function down(): void
    {
        Schema::table('order_addresses', function (Blueprint $table) {
            $table->dropColumn([
                'recipient_first_name',
                'recipient_last_name',
                'recipient_middle_name',
                'recipient_phone',
            ]);
        });
    }
};
