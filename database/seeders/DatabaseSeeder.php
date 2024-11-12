<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            UsersSeeder::class,
            UnitsTableSeeder::class,
            CategorySeeder::class,
            CostCategorySeeder::class,
        ]);
    }
}
