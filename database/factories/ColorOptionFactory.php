<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\ColorOption;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ColorOption>
 */
class ColorOptionFactory extends Factory
{
    protected $model = ColorOption::class;

    public function definition()
    {
        return [
            'title' => $this->faker->word,
            'category_id' => Category::factory(),
            'product_id' => Product::factory(),
        ];
    }
}
