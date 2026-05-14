<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('oto_banners', function (Blueprint $table) {
            $table->id();

            // Основная информация
            $table->string('name');
            $table->enum('status', ['active', 'inactive'])->default('inactive');
            $table->enum('device_type', ['desktop', 'mobile']);

            // Контент баннера
            $table->string('title')->nullable();
            $table->text('subtitle')->nullable();

            // Настройки кнопки
            $table->boolean('button_enabled')->default(true);
            $table->string('button_text')->nullable()->default('Отправить');

            // Настройки поля ввода
            $table->boolean('input_field_enabled')->default(true);
            $table->enum('input_field_type', ['email', 'phone', 'text'])->default('email');
            $table->string('input_field_label')->nullable();
            $table->string('input_field_placeholder')->nullable();
            $table->boolean('input_field_required')->default(true);

            // Настройки времени показа
            $table->integer('display_delay_seconds')->default(0); // задержка перед показом

            // Политика конфиденциальности
            $table->text('privacy_text')->nullable();

            // Сегменты для автоматического добавления клиентов
            $table->json('segment_ids')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('oto_banners');
    }
};
