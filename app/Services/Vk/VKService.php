<?php

namespace App\Services\Vk;

use App\Models\Conversation;
use App\Models\Client;
use App\Models\VKSettings;
use App\Services\Messaging\ConversationService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class VKService
{
    protected ConversationService $conversationService;

    public function __construct(ConversationService $conversationService)
    {
        $this->conversationService = $conversationService;
    }

    /**
     * Обработать входящий webhook от ВК
     */
    public function handleWebhookUpdate(array $update): array
    {
        try {
            // Валидируем обновление
            if (!$this->validateUpdate($update)) {
                Log::warning("VKService: Invalid webhook update", ['update' => $update]);
                return ['ok' => false];
            }

            // Обработка confirmation (первый запрос от ВК)
            if ($update['type'] === 'confirmation') {
                return $this->handleConfirmation();
            }

            // Обработка нового сообщения
            if ($update['type'] === 'message_new') {
                return $this->handleMessageNew($update['object']);
            }

            return ['ok' => true];

        } catch (\Exception $e) {
            Log::error("VKService: Exception in handleWebhookUpdate", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return ['ok' => false];
        }
    }

    /**
     * Валидировать webhook обновление
     */
    protected function validateUpdate(array $update): bool
    {
        // Проверяем обязательные поля
        return isset($update['type']) && isset($update['group_id']);
    }

    /**
     * Обработать confirmation запрос
     */
    protected function handleConfirmation(): array
    {
        $settings = VKSettings::first();

        if (!$settings || !$settings->confirmation_token) {
            Log::error("VKService: Confirmation token not found");
            return ['ok' => false];
        }

        // ВК требует вернуть confirmation_token
        return [
            'ok' => true,
            'confirmation_token' => $settings->confirmation_token
        ];
    }

    /**
     * Обработать новое сообщение из ВК
     */
    protected function handleMessageNew(array $messageObject): array
    {
        return DB::transaction(function () use ($messageObject) {
            // ИСПРАВЛЕНИЕ: достаём message из messageObject
            $message = $messageObject['message'] ?? null;

            if (!$message) {
                Log::warning("VKService: Message object not found", ['object' => $messageObject]);
                return ['ok' => false];
            }

            // Извлекаем данные сообщения
            $userId = $message['from_id'] ?? null;  // ← ВОТ ЗДЕСЬ
            $text = $message['text'] ?? '';
            $messageId = $message['id'] ?? null;
            $peerId = $message['peer_id'] ?? null;

            if (!$userId) {
                Log::warning("VKService: Message without user_id", ['message' => $message]);
                return ['ok' => false];
            }

            // Ищем или создаём клиента
            $client = $this->findClient($userId);

            // Ищем или создаём разговор
            $conversation = Conversation::firstOrCreate(
                [
                    'source' => 'vk',
                    'external_id' => (string)$userId,
                    'client_id' => $client?->id ?? null,
                ],
                [
                    'status' => 'active',
                    'last_message_at' => now(),
                    'unread_messages_count' => 0,
                ]
            );

            // Добавляем входящее сообщение
            $messageData = [
                'direction' => 'incoming',
                'content' => $text,
                'content_type' => 'text',
                'status' => 'delivered',
                'source_data' => [
                    'vk_message_id' => $messageId,
                    'vk_user_id' => $userId,
                    'vk_peer_id' => $peerId,
                    'attachments' => $messageObject['message']['attachments'] ?? [],
                ]
            ];

            // Обработка вложений если есть
            if (!empty($messageObject['message']['attachments'])) {
                $attachments = $this->processAttachments($messageObject['message']['attachments']);
                $messageData['attachments'] = $attachments;
            }

            $this->conversationService->addMessage($conversation, $messageData);

            event(new \App\Events\ConversationUpdated($conversation));

            return ['ok' => true];

        });
    }

    /**
     * Найти или создать клиента
     */
    protected function findClient(int $vkUserId): ?Client
    {
        try {
            // Ищем клиента по ВК ID в профиле
            $client = Client::whereHas('profile', function ($query) use ($vkUserId) {
                $query->where('vk_user_id', $vkUserId);
            })->first();

            if ($client) {
                return $client;
            } else {
                return null;
            }

        } catch (\Exception $e) {
            Log::error("VKService: Failed to create client", [
                'vk_user_id' => $vkUserId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Обработать вложения из ВК
     */
    protected function processAttachments(array $attachments): array
    {
        $processed = [];

        foreach ($attachments as $attachment) {
            $type = $attachment['type'] ?? null;

            switch ($type) {
                case 'photo':
                    if (isset($attachment['photo']['sizes'])) {
                        $largest = end($attachment['photo']['sizes']);
                        $processed[] = [
                            'type' => 'photo',
                            'url' => $largest['url'],
                            'file_name' => 'photo.jpg',
                            'attachment_id' => "photo{$attachment['photo']['owner_id']}_{$attachment['photo']['id']}"
                        ];
                    }
                    break;

                case 'doc':
                    if (isset($attachment['doc'])) {
                        $processed[] = [
                            'type' => 'document',
                            'url' => $attachment['doc']['url'],
                            'file_name' => $attachment['doc']['title'],
                            'attachment_id' => "doc{$attachment['doc']['owner_id']}_{$attachment['doc']['id']}"
                        ];
                    }
                    break;

                case 'audio':
                    if (isset($attachment['audio'])) {
                        $processed[] = [
                            'type' => 'audio',
                            'url' => $attachment['audio']['url'] ?? null,
                            'file_name' => $attachment['audio']['title'],
                            'attachment_id' => "audio{$attachment['audio']['owner_id']}_{$attachment['audio']['id']}"
                        ];
                    }
                    break;
            }
        }

        return $processed;
    }
}
