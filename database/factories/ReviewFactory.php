<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Review\Review;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Review\Review>
 */
class ReviewFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = Review::class;

    public function definition(): array
    {
        return [
            'client_id' => rand(1, 5),
            'reviewable_type' => Product::class,
            'reviewable_id' => rand(1, 9),
            'content' => $this->faker->sentence(),
            'rating' => rand(1, 5),
            'status' => $this->faker->randomElement(['new', 'published']),
            'is_verified' => rand(0, 1),
            'is_published' => rand(0, 1),
            'published_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
