<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Client;
use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with(['client', 'items.product', 'items.variant'])
            ->withCount('items');

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhereHas('client', function($q) use ($search) {
                        $q->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    });
            });
        }

        $orders = $query->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Dashboard/Orders/Index', [
            'orders' => $orders,
            'filters' => $request->only(['status', 'search']),
            'clients' => Client::select('id', 'first_name', 'last_name')->get(),
            'products' => Product::with(['variants'])
                ->where('is_active', true)
                ->get()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.variant_id' => 'nullable|exists:product_variants,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.price' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
            'status' => 'required|in:pending,processing,completed,cancelled'
        ]);

        $order = Order::create([
            'client_id' => $validated['client_id'],
            'order_number' => 'ORD-' . date('Ymd') . '-' . random_int(1000, 9999),
            'total_amount' => 0,
            'status' => $validated['status'],
            'notes' => $validated['notes'],
        ]);

        foreach ($validated['items'] as $item) {
            $order->items()->create($item);
        }

        $order->updateTotalAmount();

        return redirect()->back()->with('success', 'Заказ успешно создан');
    }

    public function show(Order $order)
    {
        $order->load(['client', 'items.product', 'items.variant']);
        
        return Inertia::render('Dashboard/Orders/Show', [
            'order' => $order,
            'products' => Product::with(['variants'])
                ->where('is_active', true)
                ->get(),
            'clients' => Client::select('id', 'first_name', 'last_name')->get()
        ]);
    }

    public function update(Request $request, Order $order)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.variant_id' => 'nullable|exists:product_variants,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.price' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
            'status' => 'required|in:pending,processing,completed,cancelled'
        ]);

        $order->update([
            'client_id' => $validated['client_id'],
            'status' => $validated['status'],
            'notes' => $validated['notes'],
        ]);

        // Удаляем существующие items и создаем новые
        $order->items()->delete();
        foreach ($validated['items'] as $item) {
            $order->items()->create($item);
        }

        $order->updateTotalAmount();

        return redirect()->back()->with('success', 'Заказ успешно обновлен');
    }
}