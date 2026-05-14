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
        Schema::table('promo_codes', function (Blueprint $table) {
            $table->enum('discount_behavior', ['replace', 'stack', 'skip'])
                ->default('stack')
                ->after('discount_type')
                ->comment('replace - заменяет скидку товара, stack - добавляется поверх, skip - не применяется к товарам со скидкой');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('promo_codes', function (Blueprint $table) {
            $table->dropColumn('discount_behavior');
        });
    }

};
