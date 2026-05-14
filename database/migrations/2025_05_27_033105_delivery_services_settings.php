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
        Schema::create('delivery_services_settings', function (Blueprint $table) {
            $table->id();
            $table->string('service_name');
            $table->string('token')->nullable();
            $table->string('secret')->nullable();
            $table->string('password')->nullable();
            $table->boolean('call_courier_to_the_office')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_services_settings');
    }
};
