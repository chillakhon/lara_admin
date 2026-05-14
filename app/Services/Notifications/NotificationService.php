<?php

namespace App\Services\Notifications;

use App\Services\Notifications\Channels\EmailNotificationChannel;
use App\Services\Notifications\Channels\TelegramNotificationChannel;
use App\Services\Notifications\Channels\WhatsAppNotificationChannel;
use App\Services\Notifications\Channels\VKNotificationChannel;
use App\Services\Notifications\Channels\WebChatNotificationChannel;
use App\Services\Notifications\Contracts\NotificationChannelInterface;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Все доступные каналы
     */
    protected array $channels = [];

    public function __construct()
    {
        $this->registerChannels();
    }

    /**
     * Регистрировать все каналы уведомлений
     */
    protected function registerChannels(): void
    {
        $this->channels = [
            'email' => new EmailNotificationChannel(),
            'telegram' => new TelegramNotificationChannel(),
            'whatsapp' => new WhatsAppNotificationChannel(),
            'vk' => new VKNotificationChannel(),
            'web_chat' => new WebChatNotificationChannel(),
        ];
    }

    /**
     * Отправить уведомление через конкретный канал
     */
    public function sendViaChannel(
        string $channel,
        string $recipientId,
        string $message,
        array $data = []
    ): bool {
        if (!isset($this->channels[$channel])) {
            Log::warning("NotificationService: Channel '{$channel}' not found");
            return false;
        }

        return $this->channels[$channel]->send($recipientId, $message, $data);
    }

    /**
     * Отправить уведомление через несколько каналов
     */
    public function sendViaChannels(
        array $channels,
        string $recipientId,
        string $message,
        array $data = []
    ): array {
        $results = [];

        foreach ($channels as $channel) {
            $results[$channel] = $this->sendViaChannel($channel, $recipientId, $message, $data);
        }

        return $results;
    }

    /**
     * Получить канал по названию
     */
    public function getChannel(string $channelName): ?NotificationChannelInterface
    {
        return $this->channels[$channelName] ?? null;
    }

    /**
     * Получить все зарегистрированные каналы
     */
    public function getAllChannels(): array
    {
        return $this->channels;
    }

    /**
     * Зарегистрировать новый канал (для расширения)
     */
    public function registerChannel(string $name, NotificationChannelInterface $channel): void
    {
        $this->channels[$name] = $channel;
        Log::info("NotificationService: Channel '{$name}' registered");
    }
}
