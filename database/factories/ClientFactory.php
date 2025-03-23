<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User; // Подключите модель User

class ClientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(), // Создаем связанного пользователя
            'bonus_balance' => $this->faker->randomFloat(2, 0, 1000), // Случайный бонусный баланс
            'client_level_id' => null, // По умолчанию null, можно изменить
        ];
    }
}
