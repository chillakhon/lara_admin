<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('user_profiles', 'middle_name')) {
                $table->string('middle_name')->nullable()->after('last_name');
            }
        });

        Schema::table('clients', function (Blueprint $table) {
            if (!Schema::hasColumn('clients', 'subscribed_to_newsletter')) {
                $table->boolean('subscribed_to_newsletter')->default(false);
            }
            if (!Schema::hasColumn('clients', 'personal_data_consent')) {
                $table->boolean('personal_data_consent')->default(false);
            }
            if (!Schema::hasColumn('clients', 'messenger_subscription')) {
                $table->boolean('messenger_subscription')->default(false);
            }
        });
    }

    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('user_profiles', 'middle_name')) {
                $table->dropColumn('middle_name');
            }
        });

        Schema::table('clients', function (Blueprint $table) {
            foreach (['subscribed_to_newsletter', 'personal_data_consent', 'messenger_subscription'] as $col) {
                if (Schema::hasColumn('clients', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
