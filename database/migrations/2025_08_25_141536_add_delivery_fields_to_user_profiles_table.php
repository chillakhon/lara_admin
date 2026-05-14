<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            // Добавляем колонки без foreign key
            if (!Schema::hasColumn('user_profiles', 'delivery_country_id')) {
                $table->unsignedBigInteger('delivery_country_id')->nullable()->after('delivery_address');
            }

            if (!Schema::hasColumn('user_profiles', 'delivery_city_id')) {
                $table->unsignedBigInteger('delivery_city_id')->nullable()->after('delivery_country_id');
            }

            if (!Schema::hasColumn('user_profiles', 'delivery_postal_code')) {
                $table->string('delivery_postal_code', 20)->nullable()->after('delivery_city_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            // Просто удаляем колонки если они существуют
            $columnsToDelete = [];
            if (Schema::hasColumn('user_profiles', 'delivery_country_id')) {
                $columnsToDelete[] = 'delivery_country_id';
            }
            if (Schema::hasColumn('user_profiles', 'delivery_city_id')) {
                $columnsToDelete[] = 'delivery_city_id';
            }
            if (Schema::hasColumn('user_profiles', 'delivery_postal_code')) {
                $columnsToDelete[] = 'delivery_postal_code';
            }

            if (!empty($columnsToDelete)) {
                $table->dropColumn($columnsToDelete);
            }
        });
    }
};
