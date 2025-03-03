<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Message;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->enum('source', ['telegram', 'whatsapp', 'web_chat']);
            $table->string('external_id')->nullable(); // ID чата в источнике
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['new', 'active', 'closed'])->default('new');
            $table->timestamp('last_message_at');
            $table->integer('unread_messages_count')->default(0);
            $table->timestamps();
            
            // Индексы для быстрого поиска
            $table->index(['source', 'external_id']);
            $table->index('status');
            $table->index('last_message_at');
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->enum('direction', [
                Message::DIRECTION_INCOMING,
                Message::DIRECTION_OUTGOING
            ]);
            $table->text('content');
            $table->enum('content_type', [
                Message::CONTENT_TYPE_TEXT,
                Message::CONTENT_TYPE_IMAGE,
                Message::CONTENT_TYPE_FILE,
                Message::CONTENT_TYPE_VOICE,
                Message::CONTENT_TYPE_VIDEO
            ])->default(Message::CONTENT_TYPE_TEXT);
            $table->enum('status', [
                Message::STATUS_SENDING,
                Message::STATUS_SENT,
                Message::STATUS_DELIVERED,
                Message::STATUS_READ,
                Message::STATUS_FAILED
            ])->default(Message::STATUS_SENDING);
            $table->json('source_data')->nullable();
            $table->timestamps();

            $table->index(['conversation_id', 'created_at']);
        });

        Schema::create('message_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('url');
            $table->string('file_name')->nullable();
            $table->integer('file_size')->nullable();
            $table->string('mime_type')->nullable();
            $table->timestamps();
        });

        Schema::create('conversation_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('role', ['manager', 'admin'])->default('manager');
            $table->timestamp('joined_at');
            $table->timestamp('left_at')->nullable();
            
            $table->unique(['conversation_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversation_participants');
        Schema::dropIfExists('message_attachments');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('conversations');
    }
}; 