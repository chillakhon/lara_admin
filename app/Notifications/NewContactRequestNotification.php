<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewContactRequestNotification extends Notification
{
    use Queueable;

    protected array $data;

    /**
     * @param array $data [
     *   'id' => номер заявки,
     *   'name' => имя клиента,
     *   'email' => email клиента,
     *   'phone' => телефон клиента,
     *   'message' => сообщение из формы,
     *   'created_at' => дата/время заявки,
     * ]
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Каналы доставки
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Формируем email-сообщение
     */
    public function toMail(object $notifiable): MailMessage
    {
        $mail = new MailMessage;

        $mail->subject('📩 Новая заявка с сайта #' . $this->data['id'])
            ->greeting('Новая заявка с сайта')
            ->line('📅 Дата: ' . $this->data['created_at'])
            ->line('👤 Имя: ' . $this->data['name'])
            ->line('📧 Email: ' . $this->data['email'])
            ->line('📞 Телефон: ' . ($this->data['phone'] ?? '—'))
            ->line('💬 Сообщение:')
            ->line($this->data['message'])
            ->action('Ответить', 'mailto:' . $this->data['email'])
            ->salutation('С уважением, команда ' . config('app.name'));

        return $mail;
    }

    /**
     * JSON-представление (например, для логов)
     */
    public function toArray(object $notifiable): array
    {
        return $this->data;
    }
}
