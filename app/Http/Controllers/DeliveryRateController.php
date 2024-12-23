<?php

namespace App\Http\Controllers;

use App\Models\DeliveryZone;
use App\Models\DeliveryRate;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DeliveryRateController extends Controller
{
    public function index(DeliveryZone $zone)
    {
        return Inertia::render('Dashboard/Delivery/Rates/Index', [
            'zone' => $zone->load('rates'),
            'method' => $zone->deliveryMethod
        ]);
    }

    public function store(Request $request, DeliveryZone $zone)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'min_weight' => 'required|numeric|min:0',
            'max_weight' => 'required|numeric|gt:min_weight',
            'min_total' => 'required|numeric|min:0',
            'max_total' => 'required|numeric|gt:min_total',
            'rate' => 'required|numeric|min:0',
            'is_active' => 'boolean'
        ]);

        $zone->rates()->create($validated);

        return redirect()->back()->with('success', 'Тариф создан');
    }

    public function update(Request $request, DeliveryRate $rate)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'min_weight' => 'required|numeric|min:0',
            'max_weight' => 'required|numeric|gt:min_weight',
            'min_total' => 'required|numeric|min:0',
            'max_total' => 'required|numeric|gt:min_total',
            'rate' => 'required|numeric|min:0',
            'is_active' => 'boolean'
        ]);

        $rate->update($validated);

        return redirect()->back()->with('success', 'Тариф обновлен');
    }

    public function destroy(DeliveryRate $rate)
    {
        $rate->delete();
        return redirect()->back()->with('success', 'Тариф удален');
    }
} 