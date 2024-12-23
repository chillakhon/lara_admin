<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run()
    {
        // Создаем роли
        $roles = [
            [
                'name' => 'Администратор',
                'slug' => 'admin',
                'description' => 'Полный административный доступ'
            ],
            [
                'name' => 'Менеджер',
                'slug' => 'manager',
                'description' => 'Доступ к управлению заказами и клиентами'
            ]
        ];

        foreach ($roles as $roleData) {
            Role::create($roleData);
        }

        // Создаем разрешения
        $permissions = [
            [
                'name' => 'Управление пользователями',
                'slug' => 'manage_users',
                'description' => 'Создание, редактирование и удаление пользователей'
            ],
            [
                'name' => 'Управление клиентами',
                'slug' => 'manage_clients',
                'description' => 'Работа с клиентской базой'
            ],
            [
                'name' => 'Просмотр отчетов',
                'slug' => 'view_reports',
                'description' => 'Доступ к просмотру отчетов'
            ]
        ];

        foreach ($permissions as $permissionData) {
            Permission::create($permissionData);
        }

        // Получаем созданные роли
        $adminRole = Role::where('slug', 'admin')->first();
        $managerRole = Role::where('slug', 'manager')->first();

        // Получаем созданные разрешения
        $manageUsers = Permission::where('slug', 'manage_users')->first();
        $manageClients = Permission::where('slug', 'manage_clients')->first();
        $viewReports = Permission::where('slug', 'view_reports')->first();

        // Назначаем разрешения ролям
        $adminRole->permissions()->attach([
            $manageUsers->id,
            $manageClients->id,
            $viewReports->id
        ]);

        $managerRole->permissions()->attach([
            $manageClients->id,
            $viewReports->id
        ]);
    }
}
