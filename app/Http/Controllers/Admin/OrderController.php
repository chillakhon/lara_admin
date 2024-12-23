<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Models\Role;
use App\Models\Client;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with([
            'client.user.profile',
            'lead',
            'items.product',
            'items.productVariant',
            'history'
        ])->latest();

        // Фильтры
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhereHas('client.user.profile', function($q) use ($search) {
                        $q->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('client', function($q) use ($search) {
                        $q->where('phone', 'like', "%{$search}%");
                    });
            });
        }

        $orders = $query->paginate(15)
            ->through(function($order) {
                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'status' => $order->status,
                    'payment_status' => $order->payment_status,
                    'total_amount' => $order->total_amount,
                    'discount_amount' => $order->discount_amount,
                    'created_at' => $order->created_at,
                    'client' => $order->client ? [
                        'id' => $order->client->id,
                        'full_name' => $order->client->user->profile->full_name,
                        'email' => $order->client->user->email,
                        'phone' => $order->client->phone,
                    ] : null,
                    'lead' => $order->lead,
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

        return Inertia::render('Dashboard/Orders/Index', [
            'orders' => $orders,
            'statuses' => Order::STATUSES,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function createClient(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'phone' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'order_id' => 'required|exists:orders,id'
        ]);

        DB::transaction(function () use ($validated) {
            // Создаем пользователя
            $user = User::create([
                'email' => $validated['email'],
                'password' => Hash::make(Str::random(12)),
                'email_verified_at' => now(),
            ]);

            // Создаем профиль пользователя
            $user->profile()->create([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
            ]);

            // Назначаем роль клиента
            $clientRole = Role::where('slug', 'client')->first();
            $user->roles()->attach($clientRole);

            // Создаем клиента
            $client = Client::create([
                'user_id' => $user->id,
                'phone' => $validated['phone'],
            ]);

            // Привязываем заказ к клиенту
            $order = Order::findOrFail($validated['order_id']);
            $order->update(['client_id' => $client->id]);

            // Добавляем запись в историю
            $order->history()->create([
                'user_id' => auth()->id(),
                'status' => $order->status,
                'comment' => 'Создан клиент и привязан к заказу'
            ]);
        });

        return redirect()->back()->with('success', 'Клиент успешно создан и привязан к заказу');
    }

    // ... остальные методы контроллера ...
} 