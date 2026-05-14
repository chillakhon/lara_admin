<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Option;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Option>
 */
class OptionFactory extends Factory
{
    protected $model = Option::class;

    public function definition()
    {
        return [
            'name' => $this->faker->word,
            'category_id' => Category::factory(),
        ];
    }
}
