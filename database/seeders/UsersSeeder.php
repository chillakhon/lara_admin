<?php

namespace Database\Seeders;

use App\Models\AdminUser;
use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Seeder;

class UsersSeeder extends Seeder
{
    public function run()
    {
        // Create admin user
        $adminUser = User::factory()->admin()->create([
            'email' => 'admin@example.com',
            'password' => bcrypt('11111111'),
        ]);

        AdminUser::factory()->create([
            'user_id' => $adminUser->id,
            'first_name' => 'Admin',
            'last_name' => 'User',
        ]);

        // Create 1000 clients
        Client::factory()
            ->count(1000)
            ->create();
    }
}
