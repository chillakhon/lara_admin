<?php

namespace App\Services\Messaging;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use App\Services\Messaging\Adapters\TelegramAdapter;
use App\Models\Client;
use Illuminate\Support\Facades\Log;

class ConversationService
{
    protected $telegramAdapter;

    public function __construct(TelegramAdapter $telegramAdapter)
    {
        $this->telegramAdapter = $telegramAdapter;
    }

    public function createConversation(string $source, string $externalId, ?int $clientId = null): Conversation
    {
        return DB::transaction(function () use ($source, $externalId, $clientId) {
            // Проверяем существование клиента
            if ($clientId && !Client::find($clientId)) {
                throw new \InvalidArgumentException("Client not found: {$clientId}");
            }

            $conversation = Conversation::create([
                'source' => $source,
                'external_id' => $externalId,
                'client_id' => $clientId,
                'status' => 'new',
                'last_message_at' => now(),
            ]);

            // Автоматическое назначение менеджера
            $this->assignManager($conversation);

            return $conversation;
        });
    }

    public function addMessage(Conversation $conversation, array $messageData): Message
    {
        // Валидация входных данных
        $this->validateMessageData($messageData);

        return DB::transaction(function () use ($conversation, $messageData) {
            $message = Message::create([
                'conversation_id' => $conversation->id,
                'direction' => $messageData['direction'],
                'content' => $messageData['content'],
                'content_type' => $messageData['content_type'] ?? 'text',
                'status' => $messageData['status'] ?? 'sending',
                'source_data' => $messageData['source_data'] ?? null,
            ]);

            // Создание вложений
            if (!empty($messageData['attachments'])) {
                foreach ($messageData['attachments'] as $attachment) {
                    $message->attachments()->create($attachment);
                }
            }

            // Обновление диалога
            $conversation->update([
                'last_message_at' => now(),
                'unread_messages_count' => DB::raw('unread_messages_count + 1'),
            ]);

            // Отправка через адаптер
            if ($messageData['direction'] === 'outgoing' && $conversation->source === 'telegram') {
                try {
                    $sent = $this->telegramAdapter->sendMessage(
                        $conversation->external_id,
                        $messageData['content'],
                        $messageData['attachments'] ?? []
                    );

                    $message->update([
                        'status' => $sent ? 'sent' : 'failed'
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to send message:', [
                        'conversation_id' => $conversation->id,
                        'error' => $e->getMessage()
                    ]);
                    $message->update(['status' => 'failed']);
                }
            }

            if ($conversation->status === 'new') {
                $conversation->update(['status' => 'active']);
            }

            return $message;
        });
    }

    protected function validateMessageData(array $data): void
    {
        $required = ['direction', 'content'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                throw new \InvalidArgumentException("Missing required field: {$field}");
            }
        }

        if (!in_array($data['direction'], ['incoming', 'outgoing'])) {
            throw new \InvalidArgumentException("Invalid direction: {$data['direction']}");
        }
    }

    public function markAsRead(Conversation $conversation): void
    {
        $conversation->update([
            'unread_messages_count' => 0
        ]);

        $conversation->messages()
            ->where('status', '!=', 'read')
            ->update(['status' => 'read']);
    }

    public function assignManager(Conversation $conversation, ?User $manager = null): void
    {
        if (!$manager) {
            // Здесь логика выбора менеджера
            // Например, выбор менеджера с наименьшей нагрузкой
            $manager = User::role('manager')
                ->withCount('assignedConversations')
                ->orderBy('assigned_conversations_count')
                ->first();
        }

        if ($manager) {
            $conversation->update(['assigned_to' => $manager->id]);
            
            $conversation->participants()->create([
                'user_id' => $manager->id,
                'role' => 'manager',
                'joined_at' => now(),
            ]);
        }
    }

    public function closeConversation(Conversation $conversation): void
    {
        $conversation->update(['status' => 'closed']);
        
        $conversation->participants()
            ->whereNull('left_at')
            ->update(['left_at' => now()]);
    }
} 