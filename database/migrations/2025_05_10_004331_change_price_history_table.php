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
        Schema::table('price_history', function (Blueprint $table) {
            $table->dropColumn(['price']);
            $table->double('price_from')->nullable()->after('item_id');
            $table->double('price_to')->nullable()->after('price_from');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('price_history', function (Blueprint $table) {
            $table->double('price')->nullable()->after('item_id');
            $table->dropColumn(['price_from', 'price_to']);
        });
    }
};
