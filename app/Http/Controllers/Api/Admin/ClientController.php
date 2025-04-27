<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
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
                        'id' => $client?->user?->id,
                        'email' => $client?->user?->email,
                        'email_verified_at' => $client?->user?->email_verified_at,
                    ],
                    'profile' => [
                        'first_name' => $client?->user?->profile?->first_name,
                        'last_name' => $client?->user?->profile?->last_name,
                        'full_name' => $client?->user?->profile?->full_name,
                        'phone' => $client?->user?->profile?->phone,
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

        return response()->json([
            'clients' => $clients,
//            'levels' => $levels,
//            'statuses' => $statuses,
//            'sortOptions' => $sortOptions,
//            'filters' => $request->only(['search', 'level', 'status', 'sort']),
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

        $client = DB::transaction(function () use ($validated) {
            $user = User::create([
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'email_verified_at' => now(),
            ]);

            $user->profile()->create([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'phone' => $validated['phone'],
            ]);

            $clientRole = Role::where('slug', 'client')->first();
            $user->roles()->attach($clientRole);

            return $user->client()->create([
                'phone' => $validated['phone'],
                'address' => $validated['address'],
                'client_level_id' => $validated['level_id'],
                'bonus_balance' => $validated['bonus_balance'] ?? 0,
            ]);
        });

        return response()->json([
            'message' => 'Клиент успешно создан',
            'client' => $client
        ], 201);
    }

    public function update(Request $request, Client $client)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'email'      => 'required|string|email|max:255|unique:users,email,' . $client->user_id,
            'phone'      => 'nullable|string|max:255',
            'address'    => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($validated, $client) {
            // 1) Обновляем профиль
            $client->user->profile()->update([
                'first_name' => $validated['first_name'],
                'last_name'  => $validated['last_name'],
                'phone'  => $validated['phone'],
            ]);

            // 2) Обновляем поля в clients
            $client->update([
                'phone'   => $validated['phone'],
                'address' => $validated['address'],
            ]);

            // 3) Обновляем почту пользователя
            $client->user()->update([
                'email' => $validated['email'],
            ]);
        });

        return response()->json([
            'message' => 'Клиент успешно обновлён',
            'client'  => $client->fresh()->load('user.profile'),
        ]);
    }


    public function destroy(Client $client)
    {
        $user = $client->user;
        $client->delete();
        $user->delete();

        return response()->json([
            'message' => 'Клиент успешно удалён'
        ]);
    }

    public function show(Client $client)
    {
        // Эйджир-загружаем профиль пользователя вместе с другими связями
        $client->load([
            'user.profile',                         // <-- добавили
            'orders' => function($query) {
                $query->latest();
            },
            'orders.items',
            'orders.items.product',
            'orders.items.productVariant',
        ]);

        // Собираем статистику по заказам
        $statistics = [
            'total_orders'        => $client->orders->count(),
            'total_spent'         => $client->orders->sum('total_amount'),
            'average_order_value' => $client->orders->avg('total_amount'),
            'last_order_date'     => $client->orders->first()?->created_at,
        ];

        // Возвращаем JSON с клиентом (включая user.profile) и статистикой
        return response()->json([
            'client'     => $client,
            'statistics' => $statistics,
        ]);
    }

}
