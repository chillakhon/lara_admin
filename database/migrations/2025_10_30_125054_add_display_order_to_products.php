<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->integer('display_order')->default(0)->after('is_active')->index();
            $table->index(['display_order', 'is_active', 'deleted_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['display_order', 'is_active', 'deleted_at']);
            $table->dropIndex(['display_order']);
            $table->dropColumn('display_order');
        });
    }
};
