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
        Schema::table('product_recipes', function (Blueprint $table) {
            if (!Schema::hasColumn('product_recipes', 'qty')) {
                $table->double('qty')->nullable()->after('product_variant_id');
            }
        });

        Schema::table('recipe_cost_rates', function (Blueprint $table) {
            if (!Schema::hasColumn('recipe_cost_rates', 'rate_per_unit')) {
                $table->double('rate_per_unit', 2)->after('cost_category_id');
            }

            if (!Schema::hasColumn('recipe_cost_rates', 'fixed_rate')) {
                $table->double('fixed_rate')->after('rate_per_unit');
            }

            $table->double('rate_per_unit', 2)->nullable()->change();
            $table->double('fixed_rate', 2)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('product_recipes', 'qty')) {
            Schema::dropColumns('product_recipes', ['qty']);
        }
    }
};
