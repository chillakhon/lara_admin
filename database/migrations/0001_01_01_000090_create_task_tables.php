<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Статусы задач
        Schema::create('task_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('color')->default('#6B7280');
            $table->integer('order')->default(0);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        // Приоритеты задач
        Schema::create('task_priorities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('color')->default('#6B7280');
            $table->integer('level')->default(0);
            $table->timestamps();
        });

        // Метки задач
        Schema::create('task_labels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('color')->default('#6B7280');
            $table->timestamps();
        });

        // Задачи
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('status_id')->constrained('task_statuses');
            $table->foreignId('priority_id')->constrained('task_priorities');
            $table->foreignId('creator_id')->constrained('users');
            $table->foreignId('assignee_id')->nullable()->constrained('users');
            $table->foreignId('parent_id')->nullable()->constrained('tasks')->onDelete('cascade');
            $table->timestamp('due_date')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('estimated_time')->nullable(); // в минутах
            $table->integer('spent_time')->default(0); // в минутах
            $table->timestamps();
            $table->softDeletes();
        });

        // Связь задач с метками
        Schema::create('task_label', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->onDelete('cascade');
            $table->foreignId('label_id')->constrained('task_labels')->onDelete('cascade');
            $table->timestamps();
        });

        // Комментарии к задачам
        Schema::create('task_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained();
            $table->text('content');
            $table->timestamps();
            $table->softDeletes();
        });

        // История изменений задач
        Schema::create('task_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained();
            $table->string('field'); // Какое поле изменено
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->timestamps();
        });

        // Вложения задач
        Schema::create('task_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained();
            $table->string('filename');
            $table->string('path');
            $table->string('mime_type');
            $table->integer('size');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('task_attachments');
        Schema::dropIfExists('task_histories');
        Schema::dropIfExists('task_comments');
        Schema::dropIfExists('task_label');
        Schema::dropIfExists('tasks');
        Schema::dropIfExists('task_labels');
        Schema::dropIfExists('task_priorities');
        Schema::dropIfExists('task_statuses');
    }
}; 