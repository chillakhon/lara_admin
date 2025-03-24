<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class AddDefaultValueToProviderClassInDeliveryMethodsTable extends Migration
{
    public function up()
    {
        Schema::table('delivery_methods', function (Blueprint $table) {
            // Устанавливаем значение по умолчанию для provider_class, если оно отсутствует
            $table->string('provider_class')->default('DefaultProvider')->change();
        });
    }

    public function down()
    {
        // Возвращаем все к первоначальному состоянию, удалив значение по умолчанию
        Schema::table('delivery_methods', function (Blueprint $table) {
            $table->string('provider_class')->default(null)->change();
        });
    }
}
