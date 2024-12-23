<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Models\ClientLevel;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $query = Client::with(['user.profile', 'level'])
            ->withCount('orders')
            ->when($request->search, function($query, $search) {
                $query->where(function($q) use ($search) {
                    $q->whereHas('user.profile', function($q) use ($search) {
                        $q->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('user', function($q) use ($search) {
                        $q->where('email', 'like', "%{$search}%");
                    });
                });
            })
            ->when($request->level, function($query, $level) {
                $query->where('client_level_id', $level);
            })
            ->when($request->status, function($query, $status) {
                switch ($status) {
                    case 'active':
                        $query->whereHas('user', function($q) {
                            $q->whereNotNull('email_verified_at');
                        });
                        break;
                    case 'inactive':
                        $query->whereHas('user', function($q) {
                            $q->whereNull('email_verified_at');
                        });
                        break;
                }
            })
            ->when($request->sort, function($query, $sort) {
                [$column, $direction] = explode(',', $sort);
                $query->orderBy($column, $direction);
            }, function($query) {
                $query->latest();
            });

        $clients = $query->paginate(10)
            ->through(function($client) {
                return [
                    'id' => $client->id,
                    'user' => [
                        'id' => $client->user->id,
                        'email' => $client->user->email,
                        'email_verified_at' => $client->user->email_verified_at,
                    ],
                    'profile' => [
                        'first_name' => $client->user->profile->first_name,
                        'last_name' => $client->user->profile->last_name,
                        'full_name' => $client->user->profile->full_name,
                    ],
                    'phone' => $client->phone,
                    'address' => $client->address,
                    'bonus_balance' => $client->bonus_balance,
                    'level' => $client->level,
                    'orders_count' => $client->orders_count,
                    'created_at' => $client->created_at,
                ];
            })
            ->withQueryString();

        $levels = ClientLevel::orderBy('name')->get();
        
        $statuses = [
            ['value' => '', 'label' => 'Все статусы'],
            ['value' => 'active', 'label' => 'Активные'],
            ['value' => 'inactive', 'label' => 'Неактивные'],
        ];

        $sortOptions = [
            ['value' => 'created_at,desc', 'label' => 'Дата регистрации (сначала новые)'],
            ['value' => 'created_at,asc', 'label' => 'Дата регистрации (сначала старые)'],
            ['value' => 'bonus_balance,desc', 'label' => 'Бонусный баланс (по убыванию)'],
            ['value' => 'bonus_balance,asc', 'label' => 'Бонусный баланс (по возрастанию)'],
            ['value' => 'orders_count,desc', 'label' => 'Количество заказов (по убыванию)'],
            ['value' => 'orders_count,asc', 'label' => 'Количество заказов (по возрастанию)'],
        ];

        return Inertia::render('Dashboard/Clients/Index', [
            'clients' => $clients,
            'levels' => $levels,
            'statuses' => $statuses,
            'sortOptions' => $sortOptions,
            'filters' => $request->only(['search', 'level', 'status', 'sort']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'phone' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'password' => 'required|string|min:8',
            'level_id' => 'nullable|exists:client_levels,id',
            'bonus_balance' => 'nullable|numeric|min:0',
        ]);

        return DB::transaction(function () use ($validated) {
            // Создаем пользователя
            $user = User::create([
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'email_verified_at' => now(), // Автоматически подтверждаем email
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
            $client = $user->client()->create([
                'phone' => $validated['phone'],
                'address' => $validated['address'],
                'client_level_id' => $validated['level_id'],
                'bonus_balance' => $validated['bonus_balance'] ?? 0,
            ]);

            return redirect()->route('dashboard.clients.show', $client)
                ->with('success', 'Клиент успешно создан');
        });
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
