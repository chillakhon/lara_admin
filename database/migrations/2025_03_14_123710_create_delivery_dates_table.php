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
        Schema::create('delivery_dates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade'); // Связь с заказом
            $table->date('date'); // Дата доставки
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('delivery_dates');
    }
};
