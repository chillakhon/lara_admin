<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ClientFactory extends Factory
{
    public function definition(): array
    {
        return [
            'bonus_balance' => $this->faker->randomFloat(2, 0, 1000),
            'client_level_id' => null,
        ];
    }
}
