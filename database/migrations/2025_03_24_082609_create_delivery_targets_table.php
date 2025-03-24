<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('delivery_targets', function (Blueprint $table) {
            $table->id();
            // Здесь добавьте нужные поля
            $table->string('name'); // пример
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('delivery_targets');
    }

};
