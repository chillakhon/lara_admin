<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MailNotification extends Notification
{
    use Queueable;

    public $title;
    public $verification_code;
    /**
     * Create a new notification instance.
     */
    public function __construct($title = null, $verification_code = null)
    {
        $this->title = $title;
        $this->verification_code = $verification_code;
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
            ->subject('Ваш код подтверждения')
            ->greeting("Здравствуйте, {$this->title}!")
            ->line('Вы запрашивали код подтверждения.')
            ->line("Ваш код подтверждения: **{$this->verification_code}**")
            ->line('Пожалуйста, введите этот код для завершения процесса.')
            ->line('Если вы не запрашивали код, просто проигнорируйте это письмо.')
            ->salutation('С уважением, команда ' . config('app.name'));
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
