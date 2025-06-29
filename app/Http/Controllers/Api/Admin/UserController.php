<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'search' => 'nullable|string',
            'role' => 'nullable|string',
            'status' => 'nullable|string|in:all,active,inactive,verified,unverified',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1,max:100'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $perPage = $request->get('per_page', 10);

        $users = User::with(['profile', 'roles', 'permissions'])
            ->when($request->search, function ($query, $search) {
                $query->whereHas('profile', function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                })
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhereHas('roles', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            })
            ->when($request->role, function ($query, $role) {
                $query->whereHas('roles', function ($q) use ($role) {
                    $q->where('slug', $role);
                });
            })
            ->when($request->status, function ($query, $status) {
                switch ($status) {
                    case 'active':
                    case 'verified':
                        $query->whereNotNull('email_verified_at');
                        break;
                    case 'inactive':
                    case 'unverified':
                        $query->whereNull('email_verified_at');
                        break;
                }
            })
            // ->when($request->boolean('only_admin_users', false), function ($query) {
            //     $query->whereDoesntHave('client');
            // })
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'users' => $users,
            'filters' => $request->only(['search', 'role', 'status']),
        ]);
    }



    public function indexDeleted(Request $request): \Illuminate\Http\JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'search' => 'nullable|string',
            'role' => 'nullable|string',
            'status' => 'nullable|string|in:all,active,inactive,verified,unverified',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1,max:100'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $perPage = $request->get('per_page', 10);

        $users = User::onlyTrashed()->with(['profile', 'roles', 'permissions'])
            ->when($request->search, function ($query, $search) {
                $query->whereHas('profile', function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                })
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhereHas('roles', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            })
            ->when($request->role, function ($query, $role) {
                $query->whereHas('roles', function ($q) use ($role) {
                    $q->where('slug', $role);
                });
            })
            ->when($request->status, function ($query, $status) {
                switch ($status) {
                    case 'active':
                    case 'verified':
                        $query->whereNotNull('email_verified_at');
                        break;
                    case 'inactive':
                    case 'unverified':
                        $query->whereNull('email_verified_at');
                        break;
                }
            })
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        $roles = Role::orderBy('name')->get();
        $permissions = Permission::orderBy('name')->get();

        return response()->json([
            'users' => $users,
            'roles' => $roles,
            'permissions' => $permissions,
            'filters' => $request->only(['search', 'role', 'status']),
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'roles' => 'required|array|min:1',
            'roles.*' => 'exists:roles,id',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ], [
            'roles.required' => 'Необходимо выбрать хотя бы одну роль',
            'roles.min' => 'Необходимо выбрать хотя бы одну роль',
            'password.min' => 'Пароль должен содержать не менее 8 символов',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            $user = User::create([
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            UserProfile::create([
                'user_id' => $user->id,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
            ]);

            $user->roles()->attach($request->roles);

            if (!empty($request->permissions)) {
                $user->permissions()->attach($request->permissions);
            }

            DB::commit();

            return response()->json([
                'message' => 'Пользователь успешно создан',
                'user' => $user->load(['profile', 'roles', 'permissions'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Ошибка при создании пользователя',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(User $user)
    {
        return response()->json([
            'user' => $user->load(['profile', 'roles', 'permissions'])
        ]);
    }

    public function update(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'roles' => 'nullable|array',
            'roles.*' => 'exists:roles,id',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            $user->update([
                'email' => $request->email,
            ]);

            $user->profile()->update([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'phone' => $request->phone,
                'address' => $request->address,
            ]);

            $user->roles()->sync($request->roles);
            $user->permissions()->sync($request->permissions ?? []);

            DB::commit();

            return response()->json([
                'message' => 'Пользователь успешно обновлен',
                'user' => $user->load(['profile', 'roles', 'permissions'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Ошибка при обновлении пользователя',
                'error' => $e->getMessage()
            ], 500);
        }
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

        $user = $request->user();

        if (!$user) {
            return response()->json(['success' => false, 'message' => "Пользователь не найден"]);
        }

        try {
            DB::beginTransaction();

            $check_for_client_with_same_email = Client::whereNull('deleted_at')
                ->where('email', $user->email)
                ->first();

            $user_profile = null;

            if ($check_for_client_with_same_email) {
                $user_profile = UserProfile
                    ::where('client_id', $check_for_client_with_same_email->id)
                    ->first();
            }

            if ($user_profile) {
                $user_profile->update([
                    'user_id' => $user->id,
                    'first_name' => $request->first_name,
                    'last_name' => $request->last_name,
                    'phone' => $request->phone,
                    'address' => $request->address,
                    'birthday' => $request->birthday,
                ]);
            } else {
                $user->profile()->updateOrCreate(
                    ['user_id' => $user->id], // condition
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
                'user' => $user->load('profile'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Ошибка при обновлении пользователя',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(User $user)
    {
        try {
            DB::beginTransaction();

            $user->roles()->detach();
            $user->permissions()->detach();
            $user->profile()->delete();
            $user->delete();

            DB::commit();

            return response()->json([
                'message' => 'Пользователь успешно удален'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Ошибка при удалении пользователя',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Полное безвозвратное удаление пользователя из базы.
     */
    public function forceDestroy($id): \Illuminate\Http\JsonResponse
    {
        try {
            DB::beginTransaction();

            // Находим пользователя, включая удаленные записи
            $user = User::withTrashed()->findOrFail($id);

            // Отсоединяем роли и права
            $user->roles()->detach();
            $user->permissions()->detach();

            // Если у пользователя есть профиль, удаляем его
            if ($user->profile) {
                $user->profile()->delete();
            }

            // Полностью удаляем пользователя из базы
            $user->forceDelete();

            DB::commit();

            return response()->json([
                'message' => 'Пользователь полностью удален из базы данных'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Ошибка при полном удалении пользователя',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Update the specified user's password.
     */
    public function updatePassword(Request $request, User $user): \Illuminate\Http\JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'old_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'password.confirmed' => 'Пароль подтверждения не совпадает.'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if (!Hash::check($request->old_password, $user->password)) {
            return response()->json([
                'message' => 'Неверный текущий пароль.'
            ], 403);
        }

        try {
            $user->update([
                'password' => Hash::make($request->password),
            ]);

            return response()->json([
                'message' => 'Пароль успешно обновлён.',
                'user' => $user->only('id', 'email') // можно добавить нужные поля
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Ошибка при обновлении пароля',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    public function updateRoles(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,id',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            $user->roles()->sync($request->roles);
            $user->permissions()->sync($request->permissions ?? []);

            DB::commit();

            return response()->json([
                'message' => 'Роли и разрешения успешно обновлены',
                'user' => $user->load(['roles', 'permissions'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Ошибка при обновлении ролей и разрешений',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Восстанавливает soft-deleted пользователя.
     */
    public function restore($id): \Illuminate\Http\JsonResponse
    {
        try {
            // Ищем пользователя, включая удалённые записи.
            $user = User::withTrashed()->findOrFail($id);

            // Если пользователь не был удалён, можно вернуть сообщение.
            if (is_null($user->deleted_at)) {
                return response()->json([
                    'message' => 'Пользователь уже активен.'
                ], 200);
            }

            // Восстанавливаем пользователя
            $user->restore();

            return response()->json([
                'message' => 'Пользователь успешно восстановлен.',
                'user' => $user->load(['profile', 'roles', 'permissions'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Ошибка при восстановлении пользователя',
                'error' => $e->getMessage()
            ], 500);
        }
    }


}
