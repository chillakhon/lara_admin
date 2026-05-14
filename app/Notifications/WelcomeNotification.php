<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeNotification extends Notification
{
    use Queueable;

    protected $title;

    /**
     * Create a new notification instance.
     */
    public function __construct($title)
    {
        $this->title = $title;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Добро пожаловать в магазин AGAIN')
            ->greeting("Здравствуйте, {$this->title}!")
            ->line('Спасибо за регистрацию в нашем магазине "AGAIN".')
            ->line('Теперь вам доступен личный кабинет, в котором вы сможете отслеживать статус всех ваших заказов.')
            ->line('Вы можете перейти в личный кабинет по этой ссылке: [again.ru](http://again8.ru/client_account/login)')
            ->salutation('С уважением, команда ' . env('APP_NAME'));
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
