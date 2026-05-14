<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('oto_banner_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('oto_banner_id')->constrained('oto_banners')->onDelete('cascade');
            $table->foreignId('client_id')->nullable()->constrained('clients')->onDelete('set null');

            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->string('session_id')->nullable();

            $table->timestamp('viewed_at');

            $table->index(['oto_banner_id', 'viewed_at']);
            $table->index(['client_id', 'viewed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('oto_banner_views');
    }
};
