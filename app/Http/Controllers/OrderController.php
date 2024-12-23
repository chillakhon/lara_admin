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
        $query = Order::with([
            'client.user.profile',
            'items.product',
            'items.productVariant'
        ])->withCount('items');

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhereHas('client.user.profile', function($q) use ($search) {
                        $q->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    });
            });
        }

        $orders = $query->latest()
            ->paginate(15)
            ->through(function($order) {
                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'status' => $order->status,
                    'payment_status' => $order->payment_status,
                    'total_amount' => $order->total_amount,
                    'discount_amount' => $order->discount_amount,
                    'items_count' => $order->items_count,
                    'created_at' => $order->created_at,
                    'client' => $order->client ? [
                        'id' => $order->client->id,
                        'full_name' => $order->client->user->profile->full_name,
                        'email' => $order->client->user->email,
                        'phone' => $order->client->phone,
                    ] : null,
                    'items' => $order->items->map(function($item) {
                        return [
                            'id' => $item->id,
                            'product' => [
                                'id' => $item->product->id,
                                'name' => $item->product->name,
                                'image' => $item->product->getFirstMediaUrl('images'),
                            ],
                            'variant' => $item->productVariant,
                            'quantity' => $item->quantity,
                            'price' => $item->price,
                        ];
                    }),
                ];
            });

        // Получаем список клиентов для формы создания заказа
        $clients = Client::with('user.profile')
            ->get()
            ->map(function($client) {
                return [
                    'id' => $client->id,
                    'full_name' => $client->user->profile->full_name,
                    'email' => $client->user->email,
                    'phone' => $client->phone,
                ];
            });

        return Inertia::render('Dashboard/Orders/Index', [
            'orders' => $orders,
            'filters' => $request->only(['status', 'search']),
            'clients' => $clients,
            'products' => Product::with(['variants'])
                ->where('is_active', true)
                ->get(),
            'statuses' => Order::STATUSES,
            'paymentStatuses' => Order::PAYMENT_STATUSES,
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
            'status' => 'required|in:new,processing,completed,cancelled',
            'payment_status' => 'required|in:pending,paid,failed,refunded',
        ]);

        $order = Order::create([
            'client_id' => $validated['client_id'],
            'order_number' => 'ORD-' . date('Ymd') . '-' . random_int(1000, 9999),
            'total_amount' => 0,
            'status' => $validated['status'],
            'payment_status' => $validated['payment_status'],
            'notes' => $validated['notes'],
        ]);

        foreach ($validated['items'] as $item) {
            $order->items()->create($item);
        }

        $order->updateTotalAmount();

        // Добавляем запись в историю
        $order->history()->create([
            'status' => $order->status,
            'payment_status' => $order->payment_status,
            'comment' => 'Заказ создан',
            'user_id' => auth()->id(),
        ]);

        return redirect()->back()->with('success', 'Заказ успешно создан');
    }

    public function show(Order $order)
    {
        $order->load([
            'client.user.profile',
            'items.product',
            'items.productVariant',
            'history.user'
        ]);
        
        return Inertia::render('Dashboard/Orders/Show', [
            'order' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'total_amount' => $order->total_amount,
                'discount_amount' => $order->discount_amount,
                'created_at' => $order->created_at,
                'notes' => $order->notes,
                'client' => $order->client ? [
                    'id' => $order->client->id,
                    'full_name' => $order->client->user->profile->full_name,
                    'email' => $order->client->user->email,
                    'phone' => $order->client->phone,
                ] : null,
                'items' => $order->items->map(function($item) {
                    return [
                        'id' => $item->id,
                        'product' => [
                            'id' => $item->product->id,
                            'name' => $item->product->name,
                            'image' => $item->product->getFirstMediaUrl('images'),
                        ],
                        'variant' => $item->productVariant,
                        'quantity' => $item->quantity,
                        'price' => $item->price,
                    ];
                }),
                'history' => $order->history->map(function($record) {
                    return [
                        'id' => $record->id,
                        'status' => $record->status,
                        'payment_status' => $record->payment_status,
                        'comment' => $record->comment,
                        'user' => $record->user ? [
                            'name' => $record->user->profile->full_name,
                        ] : null,
                        'created_at' => $record->created_at,
                    ];
                }),
            ],
            'statuses' => Order::STATUSES,
            'paymentStatuses' => Order::PAYMENT_STATUSES,
        ]);
    }

    public function update(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => 'required|string|in:new,processing,completed,cancelled',
            'payment_status' => 'required|string|in:pending,paid,failed,refunded',
            'notes' => 'nullable|string',
        ]);

        $order->update($validated);

        // Добавляем запись в историю
        $order->history()->create([
            'status' => $validated['status'],
            'payment_status' => $validated['payment_status'],
            'comment' => 'Заказ обновлен',
            'user_id' => auth()->id(),
        ]);

        return redirect()->back()->with('success', 'Заказ успешно обновлен');
    }

    public function destroy(Order $order)
    {
        $order->delete();
        return redirect()->route('dashboard.orders.index')->with('success', 'Заказ успешно удален');
    }
}