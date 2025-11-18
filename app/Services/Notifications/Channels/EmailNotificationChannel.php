<?php

namespace App\Services\Notifications\Channels;

use App\Services\Notifications\BaseNotificationChannel;
use Illuminate\Support\Facades\Mail;
use Illuminate\Mail\Message;

class EmailNotificationChannel extends BaseNotificationChannel
{
    public function send(string $recipientId, string $message, array $data = []): bool
    {
        try {
            Mail::raw($this->addUnsubscribeLink($message), function (Message $mailMessage) use ($recipientId, $data) {
                $mailMessage->to($recipientId)
                    ->subject($data['subject'] ?? 'Уведомление');
            });

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
        return 'email';
    }


    protected function addUnsubscribeLink(string $message): string
    {
        $unsubscribeUrl = url('/api/public/unsubscribe/{token}');

        $html = nl2br($message) . "<br><br>" .
            "<hr>" .
            "<p style='font-size: 12px; color: #666;'>" .
            "<a href='#' style='color: #0066cc;'>Отписаться от рассылки</a>" .
            "</p>";

        return $html;
    }


}


