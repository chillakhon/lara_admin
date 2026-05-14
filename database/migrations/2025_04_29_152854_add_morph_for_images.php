<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('images', function (Blueprint $table) {
            $table->unsignedInteger("item_id")->after('id');
            $table->string("item_type")->after('item_id');
            $table->index(["item_id", "item_type"], 'images_item_id_item_type_index');
            $table->string('blur_hash')->nullable()->after('item_type');
            $table->string('url')->nullable()->change();
            $table->string('path')->nullable()->change();
            $table->smallInteger('order')->nullable()->change();
            $table->tinyInteger('is_main')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('images', function (Blueprint $table) {
            $table->dropIndex('images_item_id_item_type_index');
            $table->dropColumn(['item_id', 'item_type', 'blur_hash']);
            $table->string('url')->change();
            $table->string('path')->change();
            $table->smallInteger('order')->change();
            $table->tinyInteger('is_main')->change();
        });
    }
};
