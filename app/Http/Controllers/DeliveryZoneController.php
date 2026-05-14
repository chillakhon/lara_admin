<?php

namespace App\Http\Controllers;

use App\Models\DeliveryMethod;
use App\Models\DeliveryZone;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DeliveryZoneController extends Controller
{
    public function index(DeliveryMethod $method)
    {
        return Inertia::render('Dashboard/Delivery/Zones/Index', [
            'method' => $method->load('zones.rates'),
        ]);
    }

    public function store(Request $request, DeliveryMethod $method)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'regions' => 'required|array',
            'regions.*' => 'string',
            'is_active' => 'boolean'
        ]);

        $zone = $method->zones()->create($validated);

        return redirect()->back()->with('success', 'Зона доставки создана');
    }

    public function update(Request $request, DeliveryZone $zone)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'regions' => 'required|array',
            'regions.*' => 'string',
            'is_active' => 'boolean'
        ]);

        $zone->update($validated);

        return redirect()->back()->with('success', 'Зона доставки обновлена');
    }

    public function destroy(DeliveryZone $zone)
    {
        $zone->delete();
        return redirect()->back()->with('success', 'Зона доставки удалена');
    }
} 