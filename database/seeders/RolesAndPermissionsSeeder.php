<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run()
    {
        // Создаем роли
        $adminRole = Role::create(['name' => 'admin']);
        $managerRole = Role::create(['name' => 'manager']);

        // Создаем разрешения
        $manageUsers = Permission::create(['name' => 'manage_users']);
        $manageClients = Permission::create(['name' => 'manage_clients']);
        $viewReports = Permission::create(['name' => 'view_reports']);

        // Назначаем разрешения ролям
        $adminRole->permissions()->attach([$manageUsers->id, $manageClients->id, $viewReports->id]);
        $managerRole->permissions()->attach([$manageClients->id, $viewReports->id]);
    }
}
