<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeliveryMethod;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DeliveryMethodController extends Controller
{
    public function index()
    {
        return Inertia::render('Dashboard/Delivery/Methods/Index', [
            'methods' => DeliveryMethod::with(['zones', 'rates'])
                ->withCount('shipments')
                ->get()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:delivery_methods',
            'description' => 'nullable|string',
            'provider_class' => 'required|string',
            'settings' => 'required|array',
            'is_active' => 'boolean'
        ]);

        DeliveryMethod::create($validated);

        return redirect()->back()->with('success', 'Метод доставки создан');
    }

    public function update(Request $request, DeliveryMethod $method)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'provider_class' => 'required|string',
            'settings' => 'required|array',
            'is_active' => 'boolean'
        ]);

        $method->update($validated);

        return redirect()->back()->with('success', 'Метод доставки обновлен');
    }
} 