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
        Schema::create('vk_settings', function (Blueprint $table) {
            $table->id();
            $table->string('community_id')->unique();
            $table->text('access_token');
            $table->string('confirmation_token')->nullable();
            $table->string('api_version')->default('5.131');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vk_settings');
    }
};
