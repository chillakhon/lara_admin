<?php

namespace Database\Factories;

use App\Models\Image;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Image>
 */
class ImageFactory extends Factory
{
    protected $model = Image::class;

    public function definition()
    {
        return [
            'path' => $this->faker->imageUrl(),
            'url' => $this->faker->url,
            'order' => $this->faker->numberBetween(1, 10),
            'is_main' => $this->faker->boolean,
        ];
    }
}
