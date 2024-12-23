<?php

namespace App\Notifications;

use App\Models\Shipment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ShipmentStatusChanged extends Notification
{
    use Queueable;

    public function __construct(private Shipment $shipment)
    {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Статус доставки изменен')
            ->line("Статус доставки вашего заказа #{$this->shipment->order->order_number} изменен на: {$this->shipment->status->name}")
            ->when($this->shipment->tracking_number, function ($message) {
                return $message->line("Трек-номер: {$this->shipment->tracking_number}");
            })
            ->action('Отследить заказ', route('orders.track', $this->shipment->tracking_number));
    }

    public function toArray($notifiable): array
    {
        return [
            'order_id' => $this->shipment->order_id,
            'shipment_id' => $this->shipment->id,
            'status' => $this->shipment->status->code,
            'tracking_number' => $this->shipment->tracking_number
        ];
    }
} 