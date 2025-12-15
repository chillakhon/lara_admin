<?php

namespace App\Services\Messaging;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Services\Messaging\Adapters\EmailAdapter;
use App\Services\Messaging\Adapters\VKAdapter;
use App\Services\Messaging\Adapters\WhatsAppAdapter;
use Illuminate\Support\Facades\DB;
use App\Services\Messaging\Adapters\TelegramAdapter;
use App\Models\Client;
use Illuminate\Support\Facades\Log;

class ConversationService
{
    protected $telegramAdapter;
    protected $vkAdapter;
    protected $whatsappAdapter;
    protected $emailAdapter;


    public function __construct(TelegramAdapter $telegramAdapter)
    {
        $this->telegramAdapter = $telegramAdapter;

        try {
            $this->vkAdapter = new VKAdapter();
        } catch (\Exception $e) {
            Log::warning("VKAdapter not available: " . $e->getMessage());
            $this->vkAdapter = null;
        }

        try {
            $this->whatsappAdapter = new WhatsAppAdapter();
        } catch (\Exception $e) {
            Log::warning("WhatsAppAdapter not available: " . $e->getMessage());
            $this->whatsappAdapter = null;
        }

        try {
            $this->emailAdapter = new EmailAdapter();
        } catch (\Exception $e) {
            Log::warning("EmailAdapter not available: " . $e->getMessage());
            $this->emailAdapter = null;
        }

    }

    public function createConversation(string $source, string $externalId, ?int $clientId = null): Conversation
    {


        return DB::transaction(function () use ($source, $externalId, $clientId) {
            // Проверяем существование клиента
            if ($clientId && !Client::find($clientId)) {
                throw new \InvalidArgumentException("Client not found: {$clientId}");
            }


            return Conversation::create([
                'source' => $source,
                'external_id' => $externalId,
                'client_id' => $clientId,
                'status' => 'new',
                'last_message_at' => now(),
            ]);
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

            // Отправка через адаптер в зависимости от источника
            if ($messageData['direction'] === 'outgoing') {
                $sent = false;

                if ($conversation->source === 'telegram') {
                    $sent = $this->telegramAdapter->sendMessage(
                        $conversation->external_id,
                        $messageData['content'],
                        $messageData['attachments'] ?? []
                    );
                } elseif ($conversation->source === 'vk' && $this->vkAdapter) {
                    $sent = $this->vkAdapter->sendMessage(
                        $conversation->external_id,
                        $messageData['content'],
                        $messageData['attachments'] ?? []
                    );
                } elseif ($conversation->source === 'whatsapp' && $this->whatsappAdapter) {
                    $sent = $this->whatsappAdapter->sendMessage(
                        $conversation->external_id,
                        $messageData['content'],
                        $messageData['attachments'] ?? []
                    );
                } elseif ($conversation->source === 'email' && $this->emailAdapter) {
                    $sent = $this->emailAdapter->sendMessage(
                        $conversation->external_id,
                        $messageData['content'],
                        $messageData['attachments'] ?? []
                    );
                }
            }

            if ($conversation->status === 'new') {
                $conversation->update(['status' => 'active']);
            }

            try {
                event(new \App\Events\MessageCreated($message));
                event(new \App\Events\ConversationUpdated($conversation));
            } catch (\Exception $e) {
                Log::warning('MessageCreated broadcast failed, but message saved:', [
                    'message_id' => $message->id,
                    'error' => $e->getMessage()
                ]);
            }

            return $message;
        });
    }

    protected function validateMessageData(array $data): void
    {
        // direction обязателен всегда
        if (!isset($data['direction'])) {
            throw new \InvalidArgumentException('Missing required field: direction');
        }

        if (!in_array($data['direction'], ['incoming', 'outgoing'], true)) {
            throw new \InvalidArgumentException("Invalid direction: {$data['direction']}");
        }

        // content и attachments могут отсутствовать по отдельности,
        // но не могут отсутствовать ОБА
        $hasContent = array_key_exists('content', $data)
            && is_string($data['content'])
            && trim($data['content']) !== '';

        $hasAttachments = array_key_exists('attachments', $data)
            && is_array($data['attachments'])
            && count($data['attachments']) > 0;

        if (!$hasContent && !$hasAttachments) {
            throw new \InvalidArgumentException(
                'Message must contain content or attachments'
            );
        }
    }

    public function markAsRead(Conversation $conversation): void
    {
        // Сбрасываем счётчик непрочитанных входящих
        $conversation->update([
            'unread_messages_count' => 0
        ]);

        // Помечаем как read только входящие сообщения
        $conversation->messages()
            ->where('direction', Message::DIRECTION_INCOMING)
            ->where('status', '!=', Message::STATUS_READ)
            ->update(['status' => Message::STATUS_READ]);
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
