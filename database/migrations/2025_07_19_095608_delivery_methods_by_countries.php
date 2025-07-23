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
        if (!Schema::hasTable('delivery_methods_by_countries')) {
            Schema::create('delivery_methods_by_countries', function (Blueprint $table) {
                $table->id();
                $table->bigInteger('country_id')->nullable();
                $table->unsignedBigInteger('delivery_method_id')->nullable();

                $table->foreign('country_id', 'fk_delivery_country')->references('id')->on('countries')->onDelete('set null')->onUpdate('cascade');
                $table->foreign('delivery_method_id', 'fk_delivery_method')->references('id')->on('delivery_methods')->onDelete('set null')->onUpdate('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_methods_by_countries');
    }
};
