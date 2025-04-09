<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Client;
use App\Models\DeliveryMethod;
use App\Models\DeliveryDate;
use App\Models\DeliveryTarget;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(), // Создаем связанного клиента
            'delivery_method_id' => DeliveryMethod::factory(), // Создаем метод доставки
            'delivery_date_id' => DeliveryDate::factory(), // Создаем дату доставки
            'delivery_target_id' => DeliveryTarget::factory(), // Создаем пункт выдачи или адрес доставки
            'order_number' => $this->faker->unique()->randomNumber(8), // Уникальный номер заказа
            'status' => $this->faker->randomElement(['pending', 'processing', 'completed', 'cancelled']), // Случайный статус
            'payment_status' => $this->faker->randomElement(['unpaid', 'paid', 'refunded']), // Случайный статус оплаты
            'total_amount' => $this->faker->randomFloat(2, 10, 1000), // Случайная общая сумма
            'discount_amount' => $this->faker->randomFloat(2, 0, 100), // Случайная сумма скидки
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'), // Дата создания заказа
        ];
    }
}
