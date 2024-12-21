<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Основная таблица отзывов
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained();
            $table->morphs('reviewable'); // Позволяет привязать отзыв к любой модели (продукт, услуга и т.д.)
            $table->text('content');
            $table->tinyInteger('rating')->unsigned(); // Оценка от 1 до 5
            $table->boolean('is_verified')->default(false); // Проверен ли отзыв модератором
            $table->boolean('is_published')->default(false); // Опубликован ли отзыв
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Таблица для хранения ответов на отзывы
        Schema::create('review_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained(); // Кто ответил (админ/менеджер)
            $table->text('content');
            $table->boolean('is_published')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // Таблица для хранения характеристик отзыва
        Schema::create('review_attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_id')->constrained()->onDelete('cascade');
            $table->string('name'); // Например: "Качество", "Доставка", "Обслуживание"
            $table->tinyInteger('rating')->unsigned(); // Оценка от 1 до 5
            $table->timestamps();
        });

        // Таблица для хранения фотографий к отзыву
        Schema::create('review_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_id')->constrained()->onDelete('cascade');
            $table->string('path');
            $table->string('url');
            $table->integer('order')->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('review_images');
        Schema::dropIfExists('review_attributes');
        Schema::dropIfExists('review_responses');
        Schema::dropIfExists('reviews');
    }
}; 