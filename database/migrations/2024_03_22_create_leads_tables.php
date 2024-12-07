<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Типы форм заявок
        Schema::create('lead_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');        // Название типа (Обратный звонок, Консультация и т.д.)
            $table->string('code')->unique(); // Уникальный код типа
            $table->text('description')->nullable();
            $table->json('required_fields')->nullable(); // Обязательные поля для этого типа
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Заявки
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_type_id')->constrained();
            $table->foreignId('client_id')->nullable()->constrained();
            $table->string('status'); // new, processing, completed, rejected
            $table->json('data');     // Все поля формы
            $table->string('source')->nullable(); // Источник заявки
            $table->string('utm_source')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->string('utm_content')->nullable();
            $table->string('utm_term')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });

        // История обработки заявок
        Schema::create('lead_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained();
            $table->string('status');
            $table->text('comment')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('lead_histories');
        Schema::dropIfExists('leads');
        Schema::dropIfExists('lead_types');
    }
}; 