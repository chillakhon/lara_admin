<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Таблица страниц
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Таблица полей
        Schema::create('fields', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('key')->unique();
            $table->string('type'); // text, textarea, wysiwyg, image, gallery, repeater, select
            $table->json('settings')->nullable(); // Настройки поля (options для select, min/max для repeater и т.д.)
            $table->boolean('required')->default(false);
            $table->integer('order')->default(0);
            $table->foreignId('parent_id')->nullable()->constrained('fields')->onDelete('cascade'); // Для полей внутри repeater
            $table->timestamps();
        });

        // Таблица значений полей
        Schema::create('field_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained()->onDelete('cascade');
            $table->foreignId('field_id')->constrained()->onDelete('cascade');
            $table->json('value')->nullable();
            $table->integer('order')->default(0); // Для сортировки элементов в repeater/gallery
            $table->foreignId('parent_id')->nullable()->constrained('field_values')->onDelete('cascade'); // Для значений внутри repeater
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('field_values');
        Schema::dropIfExists('fields');
        Schema::dropIfExists('pages');
    }
};
