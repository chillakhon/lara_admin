<?php

namespace App\Services\Notifications\Channels;

use App\Models\Conversation;
use App\Models\Message;
use App\Services\Notifications\BaseNotificationChannel;

class WebChatNotificationChannel extends BaseNotificationChannel
{
    public function send(string $recipientId, string $message, array $data = []): bool
    {
        try {
            // recipientId = conversation_id
            $conversation = Conversation::find($recipientId);

            if (!$conversation || $conversation->source !== 'web_chat') {
                return false;
            }

            // Добавляем сообщение в БД
            Message::create([
                'conversation_id' => $conversation->id,
                'direction' => 'outgoing',
                'content' => $message,
                'content_type' => 'text',
                'status' => 'sent',
                'source_data' => $data,
            ]);

            // Отправляем event в Laravel Echo
            event(new \App\Events\ConversationUpdated($conversation));

            $this->logSend($recipientId, $this->getChannelName(), $message, true);
            return true;

        } catch (\Exception $e) {
            $this->handleError($this->getChannelName(), $e);
            $this->logSend($recipientId, $this->getChannelName(), $message, false);
            return false;
        }
    }

    public function getChannelName(): string
    {
        return 'web_chat';
    }
}
