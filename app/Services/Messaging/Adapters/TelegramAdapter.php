<?php

namespace App\Services\Messaging\Adapters;

use App\Services\Messaging\AbstractMessageAdapter;
use DefStudio\Telegraph\Models\TelegraphChat;
use App\Models\Message;
use Illuminate\Support\Facades\Storage;

class TelegramAdapter extends AbstractMessageAdapter
{
    public function sendMessage(string $externalId, ?string $content, array $attachments = []): bool
    {
        try {
            $chat = TelegraphChat::where('chat_id', $externalId)->first();

            if (!$chat) {
                \Log::error("TelegramAdapter: Chat not found for external_id: {$externalId}");
                return false;
            }

            // Отправка вложений СНАЧАЛА (если есть)
            if (!empty($attachments)) {
                foreach ($attachments as $attachment) {
                    $this->sendAttachment($chat, $attachment);
                }
            }

            // Отправка текстового сообщения (если есть текст)
            if (!empty($content)) {
                $messageResponse = $chat->html($content)->send();

                if (!$messageResponse->successful()) {
                    \Log::error("TelegramAdapter: Failed to send message", [
                        'chat_id' => $externalId,
                        'error' => $messageResponse->json()
                    ]);
                    return false;
                }
            }

            return true;
        } catch (\Exception $e) {
            \Log::error("TelegramAdapter: Exception while sending message", [
                'chat_id' => $externalId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Отправка вложения в Telegram
     */
    private function sendAttachment(TelegraphChat $chat, array $attachment): bool
    {
        try {
            // Получаем полный путь к файлу
            $filePath = $attachment['file_path'] ?? null;

            if (!$filePath) {

                return false;
            }

            // Получаем абсолютный путь к файлу
            $fullPath = Storage::disk('public')->path($filePath);

            if (!file_exists($fullPath)) {

                return false;
            }


            $response = null;

            // Отправляем в зависимости от типа
            switch ($attachment['type']) {
                case 'image':
                    // Для изображений используем photo()
                    $response = $chat->photo($fullPath)->send();
                    break;

                case 'audio':
                    // Для аудио используем audio()
                    $response = $chat->audio($fullPath)->send();
                    break;

                case 'file':
                default:
                    // Для остальных файлов используем document()
                    $response = $chat->document($fullPath)->send();
                    break;
            }

            if ($response && !$response->successful()) {
                \Log::error("TelegramAdapter: Failed to send attachment", [
                    'type' => $attachment['type'],
                    'error' => $response->json()
                ]);
                return false;
            }

            return true;

        } catch (\Exception $e) {
            \Log::error("TelegramAdapter: Exception while sending attachment", [
                'attachment' => $attachment,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
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
