<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class DeliveryMethodFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->word, // Название метода доставки
            'code' => $this->faker->unique()->slug(2), // Уникальный код
            'description' => $this->faker->sentence, // Описание
            'is_active' => $this->faker->boolean(80), // 80% chance of being active
            'settings' => json_encode([ // Настройки в формате JSON
                'min_weight' => $this->faker->numberBetween(1, 10),
                'max_weight' => $this->faker->numberBetween(20, 100),
                'delivery_time' => $this->faker->numberBetween(1, 7) . ' days',
            ]),
            'provider_class' => $this->faker->randomElement([
                'App\Providers\ExpressDeliveryProvider',
                'App\Providers\StandardDeliveryProvider',
            ]), // Класс провайдера доставки
        ];
    }
}
