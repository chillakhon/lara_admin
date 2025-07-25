<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\User;
use App\Models\Role;
use App\Models\UserProfile;
use App\Traits\ClientControllerTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\ClientLevel;
use Illuminate\Support\Facades\Validator;

class ClientController extends Controller
{
    use ClientControllerTrait;

    public function index(Request $request)
    {
        $query = Client::with(['level'])
            ->whereNull('deleted_at')
            ->withCount('orders')
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->whereHas('profile', function ($q) use ($search) {
                        $q->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    })->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->level, function ($query, $level) {
                $query->where('client_level_id', $level);
            })
            ->when($request->status, function ($query, $status) {
                switch ($status) {
                    case 'active':
                        $query->whereNotNull('email_verified_at');
                        break;
                    case 'inactive':
                        $query->whereNull('email_verified_at');
                        break;
                }
            })
            ->when($request->sort, function ($query, $sort) {
                [$column, $direction] = explode(',', $sort);
                $query->orderBy($column, $direction);
            }, function ($query) {
                $query->latest();
            });

        $clients = $query->paginate(10)
            ->through(function ($client) {
                return [
                    'id' => $client->id,
                    'email' => $client?->email,
                    'verified_at' => $client?->verified_at,
                    'profile' => [
                        'first_name' => $client?->profile?->first_name,
                        'last_name' => $client?->profile?->last_name,
                        'full_name' => $client?->profile?->full_name,
                        'phone' => $client?->profile?->phone,
                    ],
                    'phone' => $client?->profile?->phone,
                    'address' => $client?->profile?->address,
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
            'password' => 'required|string|min:8',
            'phone' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'level_id' => 'nullable|exists:client_levels,id',
            'bonus_balance' => 'nullable|numeric|min:0',
        ]);

        $client = DB::transaction(function () use ($validated) {
            $client = Client::create([
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);

            $client->profile()->create([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'phone' => $validated['phone'],
                'address' => $validated['address'],
            ]);

            // $clientRole = Role::where('slug', 'client')->first();
            // $user->roles()->attach($clientRole);

            return $client->load('profile');
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
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $client->user_id,
            'phone' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($validated, $client) {
            // 1) Обновляем профиль
            $client->profile()->update([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'phone' => $validated['phone'],
                'address' => $validated['address'],
            ]);


            // 3) Обновляем почту пользователя
            $client->update([
                'email' => $validated['email'],
            ]);
        });

        return response()->json([
            'message' => 'Клиент успешно обновлён',
            'client' => $client->fresh()->load('profile'),
        ]);
    }


    public function destroy(Client $client)
    {
        // $user = $client->user;
        $client->delete();
        // $user->delete();

        return response()->json([
            'message' => 'Клиент успешно удалён'
        ]);
    }

    public function show(Client $client)
    {
        // Эйджир-загружаем профиль пользователя вместе с другими связями
        $client->load([
            'profile',                         // <-- добавили
            'orders' => function ($query) {
                $query->latest();
            },
            'orders.items',
            'orders.items.product',
            'orders.items.productVariant',
        ]);

        // Собираем статистику по заказам
        $statistics = [
            'total_orders' => $client->orders->count(),
            'total_spent' => $client->orders->sum('total_amount'),
            'average_order_value' => $client->orders->avg('total_amount'),
            'last_order_date' => $client->orders->first()?->created_at,
        ];

        // Возвращаем JSON с клиентом (включая user.profile) и статистикой
        return response()->json([
            'client' => $client,
            'statistics' => $statistics,
        ]);
    }


    public function update_profile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'birthday' => 'required|date',
            'last_name' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $client = $request->user();

        if (!$client) {
            return response()->json(['success' => false, 'message' => "Пользователь не найден"]);
        }

        try {
            DB::beginTransaction();

            $user_profile = $this->check_users_with_same_email($client);

            if ($user_profile) {
                $user_profile->update([
                    'client_id' => $client->id,
                    'first_name' => $request->first_name,
                    'last_name' => $request->last_name,
                    'phone' => $request->phone,
                    'address' => $request->address,
                    'birthday' => $request->birthday,
                ]);
            } else {
                $client->profile()->updateOrCreate(
                    ['client_id' => $client->id], // condition
                    [                          // values to update
                        'first_name' => $request->first_name,
                        'last_name' => $request->last_name,
                        'phone' => $request->phone,
                        'address' => $request->address,
                        'birthday' => $request->birthday,
                    ]
                );
            }

            DB::commit();

            return response()->json([
                'message' => 'Информация о пользователе обновлена',
                'user' => $client->load('profile'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Ошибка при обновлении пользователя',
                'error' => $e->getMessage()
            ], 500);
        }
    }

}
