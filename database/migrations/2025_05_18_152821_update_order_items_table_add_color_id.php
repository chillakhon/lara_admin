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
        Schema::table("order_items", function (Blueprint $table) {
            $table->unsignedBigInteger('color_id')->nullable()->after('product_variant_id');

            $table->foreign('color_id', 'fk_color_id')
                ->on('colors')
                ->references('id')
                ->onDelete('set null')
                ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign('fk_color_id');

            $table->dropColumn('color_id');
        });
    }
};
