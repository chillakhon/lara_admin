<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gift_card_transactions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('gift_card_id')->constrained('gift_cards')->onDelete('cascade');
            $table->foreignId('order_id')->nullable()->constrained('orders')->onDelete('set null');

            $table->enum('type', ['purchase', 'usage', 'refund', 'cancellation']);
            $table->decimal('amount', 10, 2); // Сумма операции
            $table->decimal('balance_before', 10, 2); // Баланс до операции
            $table->decimal('balance_after', 10, 2); // Баланс после операции

            $table->text('notes')->nullable(); // Комментарии

            $table->timestamps();

            // Индексы
            $table->index('gift_card_id');
            $table->index('order_id');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gift_card_transactions');
    }
};
