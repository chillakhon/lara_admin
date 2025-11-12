<?php

namespace App\Services\Notifications;

use App\Services\Notifications\Contracts\NotificationChannelInterface;
use Illuminate\Support\Facades\Log;

abstract class BaseNotificationChannel implements NotificationChannelInterface
{
    /**
     * Логировать отправку уведомления
     */
    protected function logSend(string $recipientId, string $channelName, string $message, bool $success): void
    {
        if ($success) {
            Log::info("Notification sent via {$channelName}", [
                'recipient_id' => $recipientId,
                'message_length' => strlen($message),
            ]);
        } else {
            Log::error("Failed to send notification via {$channelName}", [
                'recipient_id' => $recipientId,
            ]);
        }
    }

    /**
     * Обработать ошибку при отправке
     */
    protected function handleError(string $channelName, \Exception $e): void
    {
        Log::error("Error in {$channelName} notification channel", [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
    }
}
