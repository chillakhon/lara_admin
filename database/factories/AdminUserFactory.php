<?php

namespace Database\Factories;

use App\Models\AdminUser;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AdminUserFactory extends Factory
{
    protected $model = AdminUser::class;

    public function definition()
    {
        return [
            'user_id' => User::factory()->admin(),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'role' => 'admin',
            'permissions' => null,
        ];
    }
}
