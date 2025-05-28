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
        Schema::table('shipments', function (Blueprint $table) {
            $table->string('location_code')->nullable()->after('delivery_method_id');
            $table->string('city')->nullable()->after('shipping_address');
            $table->string('tariff_code')->nullable()->after('provider_data');
            $table->integer('period_min')->nullable()->after('cost');
            $table->integer('period_max')->nullable()->after('period_min');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropColumn([
                'location_code',
                'city',
                'tariff_code',
                'period_min',
                'period_max',
            ]);
        });
    }
};
