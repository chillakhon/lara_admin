<?php

namespace Database\Factories;

use App\Models\Material;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Material>
 */
class MaterialFactory extends Factory
{
    protected $model = Material::class;

    public function definition()
    {
        return [
            'title' => $this->faker->word,
            'unit_of_measurement' => $this->faker->randomElement(['kg', 'g', 'l', 'ml', 'm', 'cm']),
            'cost_per_unit' => $this->faker->randomFloat(2, 0.1, 100),
        ];
    }
}
