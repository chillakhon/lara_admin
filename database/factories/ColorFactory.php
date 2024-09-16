<?php

namespace Database\Factories;

use App\Models\Color;
use App\Models\ColorCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Color>
 */
class ColorFactory extends Factory
{
    protected $model = Color::class;

    public function definition()
    {
        return [
            'title' => $this->faker->colorName,
            'code' => $this->faker->hexColor,
            'color_category_id' => ColorCategory::factory(),
        ];
    }
}
