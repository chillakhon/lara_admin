<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use App\Models\ShipmentStatus;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ShipmentController extends Controller
{
    public function index(Request $request)
    {
        $query = Shipment::with(['order', 'deliveryMethod', 'status'])
            ->latest();

        if ($request->has('status')) {
            $query->where('status_id', $request->status);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('tracking_number', 'like', "%{$search}%")
                    ->orWhereHas('order', function($q) use ($search) {
                        $q->where('order_number', 'like', "%{$search}%");
                    });
            });
        }

        return Inertia::render('Dashboard/Delivery/Shipments/Index', [
            'shipments' => $query->paginate(15),
            'statuses' => ShipmentStatus::all()
        ]);
    }

    public function update(Request $request, Shipment $shipment)
    {
        $validated = $request->validate([
            'status_id' => 'required|exists:shipment_statuses,id',
            'tracking_number' => 'nullable|string',
            'notes' => 'nullable|string'
        ]);

        $shipment->update($validated);

        return redirect()->back()->with('success', 'Отправление обновлено');
    }

    public function printLabel(Shipment $shipment)
    {
        $service = $shipment->deliveryMethod->getDeliveryService();
        $pdf = $service->printLabel($shipment);

        return response($pdf)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="label.pdf"');
    }
} 