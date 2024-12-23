<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Models\UserProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with(['profile', 'roles', 'permissions'])
            ->when(request('search'), function ($query, $search) {
                $query->whereHas('profile', function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                })
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhereHas('roles', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                });
            })
            ->when(request('role'), function ($query, $role) {
                $query->whereHas('roles', function ($q) use ($role) {
                    $q->where('slug', $role);
                });
            })
            ->when(request('status'), function ($query, $status) {
                switch ($status) {
                    case 'active':
                        $query->whereNotNull('email_verified_at');
                        break;
                    case 'inactive':
                        $query->whereNull('email_verified_at');
                        break;
                    case 'verified':
                        $query->whereNotNull('email_verified_at');
                        break;
                    case 'unverified':
                        $query->whereNull('email_verified_at');
                        break;
                }
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        $roles = Role::orderBy('name')->get();
        $permissions = Permission::orderBy('name')->get();

        // Получаем все уникальные статусы пользователей
        $statuses = [
            ['value' => 'all', 'label' => 'Все'],
            ['value' => 'active', 'label' => 'Активные'],
            ['value' => 'inactive', 'label' => 'Неактивные'],
            ['value' => 'verified', 'label' => 'Подтвержденные'],
            ['value' => 'unverified', 'label' => 'Неподтвержденные'],
        ];

        return Inertia::render('Dashboard/Users/Index', [
            'users' => $users,
            'roles' => $roles,
            'permissions' => $permissions,
            'statuses' => $statuses,
            'filters' => request()->all(['search', 'role', 'status']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'roles' => 'required|array|min:1',
            'permissions' => 'present|array',
        ], [
            'roles.required' => 'Необходимо выбрать хотя бы одну роль',
            'roles.min' => 'Необходимо выбрать хотя бы одну роль',
            'roles.array' => 'Некорректный формат ролей',
            'permissions.array' => 'Некорректный формат разрешений',
            'password.min' => 'Пароль должен содержать не менее 8 символов',
        ]);

        return DB::transaction(function () use ($validated) {
            $user = User::create([
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);

            UserProfile::create([
                'user_id' => $user->id,
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
            ]);

            $user->roles()->attach($validated['roles']);
            
            if (!empty($validated['permissions'])) {
                $user->permissions()->attach($validated['permissions']);
            }

            return redirect()->back()->with('success', 'Пользователь успешно создан');
        });
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'roles' => 'required|array',
            'permissions' => 'nullable|array',
        ]);

        DB::transaction(function () use ($user, $validated) {
            $user->update([
                'email' => $validated['email'],
            ]);

            $user->profile()->update([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
            ]);

            // Синхронизируем роли и разрешения
            $user->roles()->sync($validated['roles']);
            $user->permissions()->sync($validated['permissions'] ?? []);
        });

        return redirect()->back()->with('success', 'Пользователь успешно обновлен');
    }

    public function destroy(User $user)
    {
        DB::transaction(function () use ($user) {
            // Удаляем связи с ролями и разрешениями
            $user->roles()->detach();
            $user->permissions()->detach();
            
            // Уд��ляем профиль и самого пользователя
            $user->profile()->delete();
            $user->delete();
        });

        return redirect()->back()->with('success', 'Пользователь успешно удален');
    }

    public function updateRoles(Request $request, User $user)
    {
        $validated = $request->validate([
            'roles' => 'required|array',
            'permissions' => 'nullable|array',
        ]);

        DB::transaction(function () use ($user, $validated) {
            $user->roles()->sync($validated['roles']);
            $user->permissions()->sync($validated['permissions'] ?? []);
        });

        return redirect()->back()->with('success', 'Роли и разрешения успешно обновлены');
    }
}
