<?php

namespace Database\Factories;

use App\Models\Review\Review;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReviewAttributeFactory extends Factory
{
    public function definition()
    {
        return [
            'review_id' => Review::factory(),
            'name' => $this->faker->randomElement(['Качество', 'Доставка', 'Обслуживание', 'Цена/качество']),
            'rating' => $this->faker->numberBetween(1, 5),
        ];
    }
}
