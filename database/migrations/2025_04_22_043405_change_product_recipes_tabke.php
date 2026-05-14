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
        Schema::table('product_recipes', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id')->nullable()->change();
            $table->string("component_type")->nullable()->after('recipe_id');
            $table->unsignedInteger("component_id")->nullable()->after('component_type');
            $table->index(["component_id", "component_type"]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_recipes', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id')->change();
            $table->dropIndex('product_recipes_component_id_component_type_index');
            $table->dropColumn('component_type');
            $table->dropColumn('component_id');
        });
    }
};
