<?php

namespace App\Observers;

use App\Models\Order;
use App\Facades\Delivery;

class OrderObserver
{
    public function updated(Order $order): void
    {
        // Если заказ оплачен и нет отправления
        if ($order->isDirty('payment_status') && 
            $order->isPaid() && 
            !$order->shipment()->exists()) {
            
            Delivery::createShipment($order);
        }
    }
} 