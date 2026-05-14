<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gift_cards', function (Blueprint $table) {
            $table->id();

            // Основная информация
            $table->string('code', 20)->unique()->index();
            $table->foreignId('purchase_order_id')->nullable()->constrained('orders')->onDelete('set null');
            $table->decimal('nominal', 10, 2); // 2000, 3000, 4000, 5000, 10000
            $table->decimal('balance', 10, 2); // Текущий остаток
            $table->enum('type', ['electronic', 'plastic'])->default('electronic');
            $table->enum('status', ['active','inactive', 'used', 'cancelled',])->default('inactive')->index();

            // Отправитель
            $table->string('sender_name')->nullable();
            $table->string('sender_email')->nullable();
            $table->string('sender_phone')->nullable();

            // Получатель
            $table->string('recipient_name')->nullable();
            $table->string('recipient_email')->nullable();
            $table->string('recipient_phone')->nullable();

            // Сообщение и доставка
            $table->text('message')->nullable();
            $table->enum('delivery_channel', ['email', 'whatsapp', 'sms'])->nullable();
            $table->timestamp('scheduled_at')->nullable(); // Когда запланирована отправка
            $table->string('timezone', 50)->nullable(); // +2мск и т.д.
            $table->timestamp('sent_at')->nullable(); // Когда отправлено
            $table->timestamp('delivered_at')->nullable(); // Когда получено (подтверждение)

            $table->timestamps();
            $table->softDeletes();

            // Индексы для быстрого поиска
            $table->index('purchase_order_id');
            $table->index('recipient_email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gift_cards');
    }
};
