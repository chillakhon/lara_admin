<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Добавляем поля в таблицу categories
        Schema::table('categories', function (Blueprint $table) {
            $table->boolean('show_in_catalog_menu')->default(false)->after('description');
            $table->boolean('show_as_home_banner')->default(false)->after('show_in_catalog_menu');
            $table->integer('menu_order')->default(0)->after('show_as_home_banner');
            $table->string('banner_image')->nullable()->after('menu_order');
            $table->boolean('is_new_product')->default(false)->after('banner_image');
        });

        // Добавляем поля в таблицу products
        Schema::table('products', function (Blueprint $table) {
            $table->enum('fit_type', ['low', 'tall'])->nullable()->after('absorbency_level');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn([
                'show_in_catalog_menu',
                'show_as_home_banner',
                'menu_order',
                'banner_image',
                'is_new_product',
            ]);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('fit_type');
        });
    }
};
