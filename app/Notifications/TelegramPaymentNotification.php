<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Telegram\TelegramLocation;
use NotificationChannels\Telegram\TelegramMessage;

class TelegramPaymentNotification extends Notification
{
    use Queueable;

    protected $id;
    protected $datetime;
    protected $total;

    /**
     * Create a new notification instance.
     */
    public function __construct($id, $datetime, $total)
    {
        $this->id = $id;
        $this->datetime = $datetime;
        $this->total = $total;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['telegram'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toTelegram(object $notifiable)
    {
        // u can find more in https://github.com/laravel-notification-channels/telegram

        // TelegramFile
        // TelegramContact
        // TelegramPoll
        // TelegramMessage
        // TelegramLocation
        return TelegramMessage::create()
            ->content("Спасибо за оплату! ✅")
            ->line("Мы успешно получили ваш платёж №{$this->id} от {$this->datetime} на сумму {$this->total}.")
            ->line("Если у вас есть вопросы, пожалуйста, свяжитесь с нашей поддержкой.")
            ->line("С уважением, команда Again!")
            ->button('Связаться с поддержкой', 'https://t.me/your_support_bot');
    }
    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
