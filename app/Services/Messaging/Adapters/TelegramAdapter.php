<?php

namespace App\Services\Messaging\Adapters;

use App\Services\Messaging\AbstractMessageAdapter;
use DefStudio\Telegraph\Models\TelegraphChat;
use App\Models\Message;

class TelegramAdapter extends AbstractMessageAdapter
{
    public function sendMessage(string $externalId, string $content, array $attachments = []): bool
    {
        try {
            $chat = TelegraphChat::where('chat_id', $externalId)->first();
            
            if (!$chat) {
                \Log::error("TelegramAdapter: Chat not found for external_id: {$externalId}");
                return false;
            }

            // Отправка текстового сообщения
            $messageResponse = $chat->html($content)->send();
            
            if (!$messageResponse->successful()) {
                \Log::error("TelegramAdapter: Failed to send message", [
                    'chat_id' => $externalId,
                    'error' => $messageResponse->json()
                ]);
                return false;
            }

            // Отправка вложений
            foreach ($attachments as $attachment) {
                $attachmentResponse = null;
                
                switch ($attachment['type']) {
                    case 'photo':
                        $attachmentResponse = $chat->photo($attachment['file_id'] ?? $attachment['url'])->send();
                        break;
                    case 'document':
                        $attachmentResponse = $chat->document($attachment['file_id'] ?? $attachment['url'])->send();
                        break;
                    default:
                        \Log::warning("TelegramAdapter: Unsupported attachment type", [
                            'type' => $attachment['type']
                        ]);
                }

                if ($attachmentResponse && !$attachmentResponse->successful()) {
                    \Log::error("TelegramAdapter: Failed to send attachment", [
                        'chat_id' => $externalId,
                        'error' => $attachmentResponse->json()
                    ]);
                }
            }

            return true;
        } catch (\Exception $e) {
            \Log::error("TelegramAdapter: Exception while sending message", [
                'chat_id' => $externalId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function markAsRead(string $externalId): bool
    {
        // В Telegram нет прямого API для отметки сообщений как прочитанных
        return true;
    }

    public function getSourceName(): string
    {
        return 'telegram';
    }

    public function handleMessageUpdate(array $update): void
    {
        if (isset($update['message'])) {
            // Обработка подтверждения доставки
            Message::where('source_data->message_id', $update['message']['message_id'])
                ->where('status', 'sent')
                ->update(['status' => 'delivered']);
        }
    }
} 