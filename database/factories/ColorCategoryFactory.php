<?php

namespace Database\Factories;

use App\Models\ColorCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ColorCategory>
 */
class ColorCategoryFactory extends Factory
{
    protected $model = ColorCategory::class;

    public function definition()
    {
        return [
            'title' => $this->faker->word,
        ];
    }
}
