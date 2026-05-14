<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\Client;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class UsersSeeder extends Seeder
{
    public function run()
    {
        // Создаем базовые роли
        $roles = [
            [
                'name' => 'Супер Администратор',
                'slug' => 'super-admin',
                'description' => 'Полный доступ к системе'
            ],
             [
                 'name' => 'Администратор',
                 'slug' => 'admin',
                 'description' => 'Административный доступ'
             ],
             [
                 'name' => 'Менеджер',
                 'slug' => 'manager',
                 'description' => 'Доступ менеджера'
             ],
//             [
//                 'name' => 'Клиент',
//                 'slug' => 'client',
//                 'description' => 'Доступ клиента'
//             ]
        ];

        foreach ($roles as $roleData) {
            Role::create($roleData);
        }

        $superAdminRole = Role::where('slug', 'super-admin')->first();

        // Создаем базовые разрешения
        $permissions = [
            // Пользователи
            ['name' => 'Просмотр пользователей', 'slug' => 'users.view'],
            ['name' => 'Создание пользователей', 'slug' => 'users.create'],
            ['name' => 'Редактирование пользователей', 'slug' => 'users.edit'],
            ['name' => 'Удаление пользователей', 'slug' => 'users.delete'],

            // Роли и разрешения
            ['name' => 'Управление ролями', 'slug' => 'roles.manage'],
            ['name' => 'Управление разрешениями', 'slug' => 'permissions.manage'],

            // Заказы
            ['name' => 'Просмотр заказов', 'slug' => 'orders.view'],
            ['name' => 'Управление заказами', 'slug' => 'orders.manage'],

            // Продукты
            ['name' => 'Просмотр продуктов', 'slug' => 'products.view'],
            ['name' => 'Управление продуктами', 'slug' => 'products.manage'],

            // Клиенты
            ['name' => 'Просмотр клиентов', 'slug' => 'clients.view'],
            ['name' => 'Управление клиентами', 'slug' => 'clients.manage'],

            // Настройки
            ['name' => 'Управление настройками', 'slug' => 'settings.manage'],


            ['name' => 'Управление пользователями', 'slug' => 'users.manage'],

            ['name' => 'Управление клиентами (альтернативное)', 'slug' => 'clients.manage_alt'],

            ['name' => 'Просмотр отчетов', 'slug' => 'reports.view'],


        ];

        foreach ($permissions as $permission) {
            Permission::create($permission);
        }

        // Назначаем все разрешения роли супер-админа
        $superAdminRole->permissions()->attach(Permission::all());

        // Создаем супер-админа
        $superAdmin = User::create([
            'email' => 'admin@example.com',
            'password' => bcrypt('11111111'),
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
        ]);

        // Создаем профиль супер-админа
        UserProfile::create([
            'user_id' => $superAdmin->id,
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'phone' => null,
            'address' => null,
        ]);

        // Назначаем роль супер-админа
        $superAdmin->roles()->attach($superAdminRole);

        // Создаем тестовых клиентов
        User::factory()
            ->count(10)
            ->create()
            ->each(function ($user) {
                // Создаем профиль для каждого пользователя
                UserProfile::factory()->create([
                    'user_id' => $user->id
                ]);

                // Создаем запись клиента
                // Client::factory()->create([
                //     'user_id' => $user->id
                // ]);

                // Назначаем роль клиента
                $clientRole = Role::where('slug', 'client')->first();
                $user->roles()->attach($clientRole);
            });
    }
}
