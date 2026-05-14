<?php

namespace App\Console\Commands;

use App\Models\Shipment;
use Illuminate\Console\Command;

class UpdateShipmentStatuses extends Command
{
    protected $signature = 'shipments:update-statuses';
    protected $description = 'Update shipment statuses from delivery providers';

    public function handle(): void
    {
        $shipments = Shipment::with(['deliveryMethod', 'status'])
            ->whereNotIn('status_id', [
                ShipmentStatus::where('code', ShipmentStatus::DELIVERED)->first()->id,
                ShipmentStatus::where('code', ShipmentStatus::CANCELLED)->first()->id,
                ShipmentStatus::where('code', ShipmentStatus::RETURNED)->first()->id,
            ])
            ->get();

        foreach ($shipments as $shipment) {
            try {
                $service = $shipment->deliveryMethod->getDeliveryService();
                $trackingInfo = $service->getTrackingInfo($shipment->tracking_number);
                
                // Обновляем информацию об отправлении
                $shipment->update([
                    'status_id' => $trackingInfo['status_id'],
                    'provider_data' => array_merge(
                        $shipment->provider_data ?? [], 
                        ['tracking_history' => $trackingInfo['history']]
                    )
                ]);
            } catch (\Exception $e) {
                \Log::error("Error updating shipment #{$shipment->id}: " . $e->getMessage());
            }
        }
    }
} 