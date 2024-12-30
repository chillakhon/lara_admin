<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call([
            UsersSeeder::class,
            // RolesAndPermissionsSeeder::class // Закомментируем или удалим, так как роли создаются в UsersSeeder
            UnitsTableSeeder::class,
            CategorySeeder::class,
            CostCategorySeeder::class,
            LeadTypeSeeder::class,
        ]);
    }
}
