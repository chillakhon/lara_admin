<?php

namespace Database\Factories;

use App\Models\Color;
use App\Models\ColorOption;
use App\Models\ColorOptionValue;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ColorOptionValue>
 */
class ColorOptionValueFactory extends Factory
{
    protected $model = ColorOptionValue::class;

    public function definition()
    {
        return [
            'color_id' => Color::factory(),
            'product_id' => Product::factory(),
            'color_option_id' => ColorOption::factory(),
        ];
    }
}
