<?php

namespace App\Services;

use App\Models\Order;
use App\Models\DeliveryMethod;
use Illuminate\Support\Collection;

class DeliveryManager
{
    public function getAvailableMethods(Order $order): Collection
    {
        return DeliveryMethod::where('is_active', true)
            ->get()
            ->map(function ($method) use ($order) {
                $rates = $method->getDeliveryService()->calculateRate($order);
                return [
                    'method' => $method,
                    'rates' => $rates
                ];
            });
    }

    public function createShipment(Order $order): void
    {
        $method = $order->deliveryMethod;
        $service = $method->getDeliveryService();
        $service->createShipment($order);
    }
} 