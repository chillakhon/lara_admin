<?php

namespace App\Http\Controllers;

use App\Models\Shipment;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TrackingController extends Controller
{
    public function show($trackingNumber)
    {
        $shipment = Shipment::where('tracking_number', $trackingNumber)
            ->with(['order', 'deliveryMethod', 'status'])
            ->firstOrFail();

        $service = $shipment->deliveryMethod->getDeliveryService();
        $trackingInfo = $service->getTrackingInfo($trackingNumber);

        return Inertia::render('Tracking/Show', [
            'shipment' => $shipment,
            'trackingInfo' => $trackingInfo
        ]);
    }
} 