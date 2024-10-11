<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with(['client', 'items.product']);

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->has('sort')) {
            $query->orderBy($request->sort, $request->direction ?? 'asc');
        } else {
            $query->latest();
        }

        $orders = $query->paginate(15)->withQueryString();

        return Inertia::render('Dashboard/Orders/Index', [
            'orders' => $orders,
            'filters' => $request->only(['status', 'sort', 'direction']),
        ]);
    }

    public function update(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,processing,completed,cancelled',
            'notes' => 'nullable|string',
            'items' => 'required|array',
            'items.*.id' => 'required|exists:order_items,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        $order->update([
            'status' => $validated['status'],
            'notes' => $validated['notes'],
        ]);

        foreach ($validated['items'] as $item) {
            $orderItem = $order->items()->find($item['id']);
            $orderItem->update(['quantity' => $item['quantity']]);
        }

        $order->updateTotalAmount();

        return redirect()->back()->with('success', 'Заказ успешно обновлен');
    }

    public function destroy(Order $order)
    {
        $order->delete();
        return redirect()->back()->with('success', 'Заказ успешно удален');
    }

    public function show(Order $order)
    {
        $order->load('client');
        return Inertia::render('Dashboard/Orders/Show', ['order' => $order]);
    }
}
