<?php

namespace App\Services\Notifications\Channels;

use App\Services\Messaging\Adapters\WhatsAppAdapter;
use App\Services\Notifications\BaseNotificationChannel;

class WhatsAppNotificationChannel extends BaseNotificationChannel
{
    protected WhatsAppAdapter $adapter;

    public function __construct()
    {
        try {
            $this->adapter = new WhatsAppAdapter();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('WhatsAppAdapter not available: ' . $e->getMessage());
        }
    }

    public function send(string $recipientId, string $message, array $data = []): bool
    {
        try {
            if (!isset($this->adapter)) {
                return false;
            }

            // recipientId = phone_number
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
        return 'whatsapp';
    }
}
