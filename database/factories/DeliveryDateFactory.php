<?php

namespace Database\Factories;

use App\Models\DeliveryDate;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

class DeliveryDateFactory extends Factory
{
    protected $model = DeliveryDate::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(), // Создаем связанный заказ
            'date' => $this->faker->dateTimeBetween('now', '+1 month'), // Случайная дата в ближайший месяц
        ];
    }
}
