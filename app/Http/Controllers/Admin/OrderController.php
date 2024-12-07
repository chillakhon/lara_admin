<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Client;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with(['client', 'lead', 'items.product', 'history'])
            ->latest();

        // Фильтры
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhereHas('client', function($q) use ($search) {
                        $q->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
            });
        }

        return Inertia::render('Dashboard/Orders/Index', [
            'orders' => $query->paginate(15),
            'statuses' => Order::getStatuses(),
        ]);
    }

    public function update(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => 'required|string',
            'comment' => 'nullable|string',
        ]);

        $order->update([
            'status' => $validated['status']
        ]);

        $order->history()->create([
            'user_id' => $request->user()->id,
            'status' => $validated['status'],
            'comment' => $validated['comment'],
        ]);

        return redirect()->back();
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
                'type' => User::TYPE_CLIENT
            ]);

            // Создаем клиента
            $client = Client::create([
                'user_id' => $user->id,
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
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
} 