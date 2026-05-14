<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static bool sendViaChannel(string $channel, string $recipientId, string $message, array $data = [])
 * @method static array sendViaChannels(array $channels, string $recipientId, string $message, array $data = [])
 * @method static \App\Services\Notifications\Contracts\NotificationChannelInterface|null getChannel(string $channelName)
 * @method static array getAllChannels()
 * @method static void registerChannel(string $name, \App\Services\Notifications\Contracts\NotificationChannelInterface $channel)
 */
class Notification extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'notification-service';
    }
}
