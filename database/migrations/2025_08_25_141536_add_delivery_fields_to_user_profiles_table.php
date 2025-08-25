<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
//            $table->foreignId('delivery_country_id')->nullable()->constrained('countries')->after('delivery_address');
            $table->foreignId('delivery_city_id')->nullable()->constrained('cities')->after('delivery_country_id');
            $table->string('delivery_postal_code', 20)->nullable()->after('delivery_city_id');
        });
    }

    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
//            $table->dropForeign(['delivery_country_id']);
            $table->dropForeign(['delivery_city_id']);
            $table->dropColumn(['delivery_country_id', 'delivery_city_id', 'delivery_postal_code']);
        });
    }
};
