<?php

namespace Database\Factories;

use App\Models\Option;
use App\Models\OptionValue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OptionValue>
 */
class OptionValueFactory extends Factory
{
    protected $model = OptionValue::class;

    public function definition()
    {
        return [
            'option_id' => Option::factory(),
            'value' => $this->faker->word,
        ];
    }
}
