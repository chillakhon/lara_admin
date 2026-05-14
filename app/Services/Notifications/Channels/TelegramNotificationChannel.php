<?php

namespace App\Services\Notifications\Channels;

use App\Services\Notifications\BaseNotificationChannel;
use DefStudio\Telegraph\Facades\Telegraph;
use DefStudio\Telegraph\Models\TelegraphBot;
use Illuminate\Support\Facades\Log;

class TelegramNotificationChannel extends BaseNotificationChannel
{
    public function send(string $recipientId, string $message, array $data = []): bool
    {
        try {
            // recipientId = telegram_user_id (chat_id)
            $bot = TelegraphBot::first();

            if (!$bot) {
                Log::warning('TelegramNotificationChannel: No bot found');
                return false;
            }

            // Используем Telegraph Facade для отправки
            Telegraph::chat($recipientId)
                ->message($message)
                ->send();

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
        return 'telegram';
    }
}
