<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LoginNotification extends Notification
{
    use Queueable;

    protected $name;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Добро пожаловать обратно в магазин AGAIN')
            ->greeting("Здравствуйте, {$this->name}!")
            ->line('Рады снова видеть вас в магазине "AGAIN".')
            ->line('Вы можете перейти в личный кабинет по этой ссылке: [again.ru](http://again8.ru/client_account/login)')
            ->salutation('С уважением, команда ' . env('APP_NAME'));
    }
}
