<?php

namespace App\Services\Delivery;

use App\Models\Order;
use App\Models\Shipment;
use App\Models\ShipmentStatus;
use CdekSDK\CdekClient;
use Illuminate\Support\Collection;

class CdekDeliveryService extends DeliveryService
{
    private CdekClient $client;

    public function __construct(array $settings)
    {
        parent::__construct($settings);
        
        $this->client = new CdekClient(
            $settings['account'],
            $settings['password'],
            $settings['test_mode'] ?? false
        );
    }

    public function calculateRate(Order $order): Collection
    {
        // Реализация расчета стоимости через API СДЭК
        return collect([
            'price' => 0,
            'estimated_days' => 0
        ]);
    }

    public function createShipment(Order $order): Shipment
    {
        // Создание отправления в СДЭК
        return Shipment::create([
            'order_id' => $order->id,
            'delivery_method_id' => $order->delivery_method_id,
            'status_id' => ShipmentStatus::where('code', ShipmentStatus::NEW)->first()->id,
            // ... остальные поля
        ]);
    }

    public function getTrackingInfo(string $trackingNumber): array
    {
        // Получение информации о статусе доставки
        return [];
    }

    public function cancelShipment(Shipment $shipment): bool
    {
        // Отмена отправления
        return true;
    }

    public function printLabel(Shipment $shipment): string
    {
        // Получение PDF с накладной
        return '';
    }
} 