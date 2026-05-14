<?php

namespace App\Services\Notifications\Channels;

use App\Services\Messaging\Adapters\VKAdapter;
use App\Services\Notifications\BaseNotificationChannel;

class VKNotificationChannel extends BaseNotificationChannel
{
    protected VKAdapter $adapter;

    public function __construct()
    {
        try {
            $this->adapter = new VKAdapter();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('VKAdapter not available: ' . $e->getMessage());
        }
    }

    public function send(string $recipientId, string $message, array $data = []): bool
    {
        try {
            if (!isset($this->adapter)) {
                return false;
            }

            // recipientId = vk_user_id
            $success = $this->adapter->sendMessage($recipientId, $message);

            $this->logSend($recipientId, $this->getChannelName(), $message, $success);
            return $success;

        } catch (\Exception $e) {
            $this->handleError($this->getChannelName(), $e);
            $this->logSend($recipientId, $this->getChannelName(), $message, false);
            return false;
        }
    }

    public function getChannelName(): string
    {
        return 'vk';
    }
}
