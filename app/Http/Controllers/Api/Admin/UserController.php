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
use Illuminate\Support\Facades\Storage;


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
            'birthday' => 'nullable|date',
            'last_name' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255|unique:users,email,' . $request->user()->id,
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

            // Если email передан и он отличается от текущего — обновляем
            if ($request->filled('email') && $request->email !== $user->email) {
                $user->email = $request->email;
                $user->save();
            }

            $check_for_client_with_same_email = Client::whereNull('deleted_at')
                ->where('email', $user->email)
                ->first();

            $user_profile = null;

            if ($check_for_client_with_same_email) {
                $user_profile = UserProfile::where('client_id', $check_for_client_with_same_email->id)->first();
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
                    ['user_id' => $user->id],
                    [
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


    public function update_profile_image(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => "Пользователь не найден"], 404);
        }

        try {
            DB::beginTransaction();

            $profile = $user->profile; // может быть null

            // Удалим старое изображение, если есть
            if ($profile && $profile->image && Storage::disk('public')->exists($profile->image)) {
                Storage::disk('public')->delete($profile->image);
            }

            $imagePath = $request->file('image')->store('user_profiles', 'public');

            // Если профиля нет — создаём минимальный профиль с image
            if ($profile) {
                $profile->update(['image' => $imagePath]);
            } else {
                $user->profile()->create([
                    'user_id' => $user->id,
                    'first_name' => '',
                    'last_name' => '',
                    'image' => $imagePath,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Аватар обновлён',
                'user' => $user->load('profile'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Ошибка при обновлении аватара',
                'error' => $e->getMessage()
            ], 500);
        }
    }

// ...

    public function getProfileImage(Request $request)
    {
        // Можно передать либо path, либо user_id (user_id имеет приоритет)
        $userId = $request->get('user_id');
        $path = $request->get('path');

        // Если передан user_id — попробуем получить image из профиля
        if ($userId) {
            $user = User::with('profile')->find($userId);
            if ($user && $user->profile && !empty($user->profile->image)) {
                $path = $user->profile->image;
            }
        } else {
            // Если авторизован и не передан user_id, можно по умолчанию вернуть текущего пользователя
            if (!$path && $request->user()) {
                $profile = $request->user()->profile;
                if ($profile && !empty($profile->image)) {
                    $path = $profile->image;
                }
            }
        }

        // Если пути нет — возвращаем дефолтную картинку
        if (!$path) {
            $default = public_path('images/default-avatar.png'); // убедись, что файл есть
            return response()->file($default, [
                'Cache-Control' => 'public, max-age=3600, must-revalidate'
            ]);
        }

        // Безопасность: запретим попытки directory traversal
        if (strpos($path, '..') !== false) {
            return response()->json(['message' => 'Invalid path'], 400);
        }

        // Нормализуем путь: допустимы варианты "user_profiles/xxx.jpg" или просто "xxx.jpg"
        $path = ltrim($path, '/');

        // Если в базе случайно попал полный URL — попробуем извлечь только имя/путь после "user_profiles"
        // (не обязателен, но помогает если в DB есть Storage::url(...))
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            // попытаемся найти подстроку 'user_profiles' и взять остаток
            $pos = strpos($path, 'user_profiles/');
            if ($pos !== false) {
                $path = substr($path, $pos);
            } else {
                // если это внешний URL — редиректим на него (опционально) или возвращаем дефолт
                return redirect($path);
            }
        }

        // Компонуем полный путь к файлу в storage/app/public
        $filePath = storage_path('app/public/' . $path);

        if (!file_exists($filePath) || !is_file($filePath)) {
            // fallback на дефолт
            $default = public_path('images/default-avatar.png');
            return response()->file($default, [
                'Cache-Control' => 'public, max-age=3600, must-revalidate'
            ]);
        }

        // Вернём файл с заголовками кеширования
        return response()->file($filePath, [
            'Cache-Control' => 'public, max-age=3600, must-revalidate'
        ]);
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
