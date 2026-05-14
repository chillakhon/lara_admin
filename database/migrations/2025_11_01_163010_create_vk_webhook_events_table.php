<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('vk_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_id')->unique();
            $table->string('type'); // message_new, message_typing_state и т.д.
            $table->json('data')->nullable();
            $table->timestamp('received_at');
            $table->timestamps();

            $table->index('event_id');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vk_webhook_events');
    }
};
