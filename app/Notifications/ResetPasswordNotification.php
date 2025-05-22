<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends ResetPassword
{
    use Queueable;

    public $token;

    /**
     * Create a new notification instance.
     */
    public function __construct($token)
    {
        $this->token = $token;
    }

    public function toMail($notifiable)
    {
        $url = url(env('FRONDEND_URL') . '/password/reset?token=' . $this->token . '&email=' . urlencode($notifiable->email));

        return (new MailMessage)
            ->subject('Сброс пароля')
            ->greeting('Добрый день!')
            ->line('Мы получили запрос на сброс пароля для вашей учётной записи.')
            ->action('Перейти к сбросу пароля', $url)
            ->line('Если вы не отправляли этот запрос, просто проигнорируйте это письмо. Ваш пароль останется без изменений.')
            ->salutation('С уважением, команда поддержки ' . env('APP_NAME'));
    }
}
