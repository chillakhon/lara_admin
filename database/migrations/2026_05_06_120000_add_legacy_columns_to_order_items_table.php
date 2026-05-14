<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            if (!Schema::hasColumn('order_items', 'legacy_name')) {
                $table->string('legacy_name')->nullable()->after('color_id');
            }
            if (!Schema::hasColumn('order_items', 'legacy_sku')) {
                $table->string('legacy_sku')->nullable()->after('legacy_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            if (Schema::hasColumn('order_items', 'legacy_sku')) {
                $table->dropColumn('legacy_sku');
            }
            if (Schema::hasColumn('order_items', 'legacy_name')) {
                $table->dropColumn('legacy_name');
            }
        });
    }
};
