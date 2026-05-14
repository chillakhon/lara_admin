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
        Schema::table('cart', function (Blueprint $table) {
            $table->decimal('total', 10, 2)
                ->after('status')
                ->nullable();
            $table->decimal('total_original', 10, 2)
                ->after('total')
                ->nullable();
            $table->decimal('total_discount', 10, 2)
                ->after('total_original')
                ->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cart', function (Blueprint $table) {
            $table->dropColumn(['total', 'total_original', 'total_discount']);
        });
    }
};
