<?php

namespace Database\Factories;

use App\Models\Review\ReviewResponse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Review\ReviewResponse>
 */
class ReviewResponseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */

    protected $model = ReviewResponse::class;

    public function definition(): array
    {
        return [
            'user_id' => rand(1, 3),
            "review_id" => rand(1, 100),
            'content' => $this->faker->sentence(),
            'is_published' => 1,
            'created_at' => now()->subDays(rand(0, 30)),
            'updated_at' => now(),
        ];
    }
}
