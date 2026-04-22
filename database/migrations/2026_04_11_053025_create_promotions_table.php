<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();

            // Основная информация
            $table->string('name')->comment('Название акции');
            $table->text('description')->nullable()->comment('Описание акции');

            // Период действия
            $table->timestamp('starts_at')->nullable()->comment('Дата начала');
            $table->timestamp('ends_at')->nullable()->comment('Дата окончания');

            // Условия активации
            $table->decimal('min_purchase_amount', 10, 2)->comment('Минимальная сумма покупки');

            // Настройки совместимости
            $table->boolean('allow_promo_codes')->default(false)->comment('Разрешить промокоды и скидки');

            // Статус
            $table->boolean('is_active')->default(true);

            // Приоритет (если несколько акций подходят)
            $table->integer('priority')->default(0)->comment('Чем выше, тем приоритетнее');

            // Ограничения
            $table->integer('max_uses')->nullable()->comment('Максимальное количество использований');
            $table->integer('times_used')->default(0)->comment('Сколько раз использована');

            $table->timestamps();
            $table->softDeletes();

            // Индексы
            $table->index(['is_active', 'starts_at', 'ends_at']);
            $table->index('priority');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};
