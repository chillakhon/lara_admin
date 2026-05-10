<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('user_profiles', 'delivery_region')) {
                $table->string('delivery_region')->nullable()->after('delivery_city_id');
            }
            if (!Schema::hasColumn('user_profiles', 'delivery_street')) {
                $table->string('delivery_street')->nullable()->after('delivery_region');
            }
            if (!Schema::hasColumn('user_profiles', 'delivery_house')) {
                $table->string('delivery_house', 50)->nullable()->after('delivery_street');
            }
            if (!Schema::hasColumn('user_profiles', 'delivery_apartment')) {
                $table->string('delivery_apartment', 50)->nullable()->after('delivery_house');
            }
        });

        Schema::table('clients', function (Blueprint $table) {
            if (!Schema::hasColumn('clients', 'rfm_segment')) {
                $table->string('rfm_segment', 32)->nullable();
            }
            if (!Schema::hasColumn('clients', 'group_name')) {
                $table->string('group_name')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            foreach (['delivery_region', 'delivery_street', 'delivery_house', 'delivery_apartment'] as $col) {
                if (Schema::hasColumn('user_profiles', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('clients', function (Blueprint $table) {
            foreach (['rfm_segment', 'group_name'] as $col) {
                if (Schema::hasColumn('clients', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
