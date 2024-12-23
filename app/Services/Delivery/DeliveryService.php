<?php

namespace App\Services\Delivery;

use App\Models\Order;
use App\Models\Shipment;
use Illuminate\Support\Collection;

abstract class DeliveryService
{
    protected array $settings;

    public function __construct(array $settings)
    {
        $this->settings = $settings;
    }

    /**
     * Расчет стоимости доставки
     */
    abstract public function calculateRate(Order $order): Collection;

    /**
     * Создание отправления в службе доставки
     */
    abstract public function createShipment(Order $order): Shipment;

    /**
     * Получение информации об отправлении
     */
    abstract public function getTrackingInfo(string $trackingNumber): array;

    /**
     * Отмена отправления
     */
    abstract public function cancelShipment(Shipment $shipment): bool;

    /**
     * Печать накладной
     */
    abstract public function printLabel(Shipment $shipment): string;
} 