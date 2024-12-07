<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {

        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Типы полей
        Schema::create('field_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type');
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        // Группы полей
        Schema::create('field_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        // Поля
        Schema::create('fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('field_type_id')->constrained()->onDelete('cascade');
            $table->foreignId('field_group_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('key')->unique();
            $table->boolean('required')->default(false);
            $table->json('settings')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Блоки контента
        Schema::create('content_blocks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('key')->unique();
            $table->foreignId('field_group_id')->constrained();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('page_contents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained()->onDelete('cascade');
            $table->foreignId('content_block_id')->constrained()->onDelete('cascade');
            $table->string('language', 2)->default('ru');
            $table->json('field_values')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            // Уникальный индекс для предотвращения дублирования блоков на одной странице
            $table->unique(['page_id', 'content_block_id', 'language']);
        });


        // Значения полей
        Schema::create('field_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('field_id')->constrained()->onDelete('cascade');
            $table->foreignId('page_content_id')->constrained()->onDelete('cascade');
            $table->json('value')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {


        Schema::dropIfExists('field_values');
        Schema::dropIfExists('content_blocks');
        Schema::dropIfExists('fields');
        Schema::dropIfExists('field_groups');
        Schema::dropIfExists('field_types');
        Schema::dropIfExists('pages');
        Schema::dropIfExists('page_contents');
    }
};
