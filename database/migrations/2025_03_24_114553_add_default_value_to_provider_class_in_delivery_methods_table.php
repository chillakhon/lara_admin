<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class AddDefaultValueToProviderClassInDeliveryMethodsTable extends Migration
{
    public function up()
    {
        Schema::table('delivery_methods', function (Blueprint $table) {
            if (Schema::hasColumn('delivery_methods', 'provider_class')) {
                $table->string('provider_class')->default('DefaultProvider')->change();
            }
        });
    }

    public function down()
    {
        Schema::table('delivery_methods', function (Blueprint $table) {
            if (Schema::hasColumn('delivery_methods', 'provider_class')) {
                // Remove default by setting it to null
                $table->string('provider_class')->default(null)->change();
            }
        });
    }
}
