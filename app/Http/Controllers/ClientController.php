<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $query = Client::with('user')
            ->when($request->search, function($query, $search) {
                $query->where(function($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhereHas('user', function($q) use ($search) {
                            $q->where('email', 'like', "%{$search}%");
                        });
                });
            });

        $clients = $query->latest()->paginate(10)
            ->through(function($client) {
                return [
                    'id' => $client->id,
                    'full_name' => $client->full_name,
                    'email' => $client->user->email,
                    'phone' => $client->phone,
                    'bonus_balance' => $client->bonus_balance,
                    'total_orders' => $client->orders_count,
                    'created_at' => $client->created_at
                ];
            });

        return Inertia::render('Dashboard/Clients/Index', [
            'clients' => $clients,
            'filters' => $request->only(['search'])
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
            'phone' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
        ]);

        $user = User::create([
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'type' => 'client',
        ]);

        $client = $user->client()->create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'phone' => $validated['phone'],
            'address' => $validated['address'],
        ]);

        return redirect()->back()->with('success', 'Client created successfully');
    }

    public function update(Request $request, Client $client)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $client->user_id,
            'phone' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
        ]);

        $client->update([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'phone' => $validated['phone'],
            'address' => $validated['address'],
        ]);

        $client->user->update([
            'email' => $validated['email'],
        ]);

        return redirect()->back()->with('success', 'Client updated successfully');
    }

    public function destroy(Client $client)
    {
        $user = $client->user;
        $client->delete();
        $user->delete();
        return redirect()->route('dashboard.clients.index')->with('success', 'Client deleted successfully');
    }

    public function show(Client $client)
    {
        $client->load([
            'user',
            'orders' => function($query) {
                $query->latest();
            },
            'orders.items',
            'orders.items.product',
            'orders.items.productVariant'
        ]);

        // Подготовка статистики
        $statistics = [
            'total_orders' => $client->orders->count(),
            'total_spent' => $client->orders->sum('total_amount'),
            'average_order_value' => $client->orders->avg('total_amount'),
            'last_order_date' => $client->orders->first()?->created_at,
        ];

        return Inertia::render('Dashboard/Clients/Show', [
            'client' => $client,
            'statistics' => $statistics
        ]);
    }
}
