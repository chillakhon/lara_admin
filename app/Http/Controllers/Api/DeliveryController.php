<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeliveryMethod;
use App\Models\Order;
use App\Models\Shipment;
use Illuminate\Http\Request;

class DeliveryController extends Controller
{
    public function calculate(Request $request)
    {
        $validated = $request->validate([
            'delivery_method_id' => 'required|exists:delivery_methods,id',
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'delivery_address' => 'required|array',
            'delivery_address.city' => 'required|string',
            'delivery_address.postal_code' => 'required|string'
        ]);

        $method = DeliveryMethod::findOrFail($request->delivery_method_id);
        $service = $method->getDeliveryService();

        // Создаем временный заказ для расчета
        $order = new Order([
            'items' => $request->items,
            'delivery_address' => $request->delivery_address
        ]);

        $rates = $service->calculateRate($order);

        return response()->json([
            'success' => true,
            'rates' => $rates
        ]);
    }

    public function getAvailableMethods(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'delivery_address' => 'required|array',
            'delivery_address.city' => 'required|string',
            'delivery_address.postal_code' => 'required|string'
        ]);

        $order = new Order([
            'items' => $request->items,
            'delivery_address' => $request->delivery_address
        ]);

        $methods = DeliveryMethod::where('is_active', true)
            ->get()
            ->map(function ($method) use ($order) {
                $service = $method->getDeliveryService();
                $rates = $service->calculateRate($order);
                
                return [
                    'method' => $method,
                    'rates' => $rates
                ];
            });

        return response()->json([
            'success' => true,
            'methods' => $methods
        ]);
    }

    public function track($trackingNumber)
    {
        $shipment = Shipment::where('tracking_number', $trackingNumber)
            ->with(['order', 'deliveryMethod', 'status'])
            ->firstOrFail();

        $service = $shipment->deliveryMethod->getDeliveryService();
        $trackingInfo = $service->getTrackingInfo($trackingNumber);

        return response()->json([
            'success' => true,
            'shipment' => $shipment,
            'tracking_info' => $trackingInfo
        ]);
    }
} 