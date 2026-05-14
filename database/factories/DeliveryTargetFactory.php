<?php

namespace Database\Factories;

use App\Models\DeliveryTarget;
use Illuminate\Database\Eloquent\Factories\Factory;

class DeliveryTargetFactory extends Factory
{
    protected $model = DeliveryTarget::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->word, // Название пункта доставки
            'address' => $this->faker->address, // Адрес пункта доставки
            'is_active' => $this->faker->boolean(90), // 90% chance of being active
        ];
    }
}
