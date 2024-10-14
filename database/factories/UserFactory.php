<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition()
    {
        return [
            'email' => $this->faker->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => bcrypt('password'), // default password for all users
            'remember_token' => Str::random(10),
            'type' => 'client', // default type
        ];
    }

    public function admin()
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'admin',
            ];
        });
    }

    public function client()
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'client',
            ];
        });
    }
}
