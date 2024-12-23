<?php

namespace App\Observers;

use App\Models\Shipment;
use App\Notifications\ShipmentStatusChanged;

class ShipmentObserver
{
    public function updated(Shipment $shipment): void
    {
        if ($shipment->isDirty('status_id')) {
            // Уведомляем клиента
            if ($shipment->order->client) {
                $shipment->order->client->notify(
                    new ShipmentStatusChanged($shipment)
                );
            }

            // Добавляем запись в историю заказа
            $shipment->order->history()->create([
                'user_id' => auth()->id(),
                'status' => $shipment->order->status,
                'comment' => "Статус доставки изменен на: {$shipment->status->name}"
            ]);
        }
    }
} 