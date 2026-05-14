<?php

namespace Database\Factories;

use App\Models\Material;
use App\Models\MaterialConversion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\MaterialConversion>
 */
class MaterialConversionFactory extends Factory
{
    protected $model = MaterialConversion::class;

    public function definition()
    {
        return [
            'material_id' => Material::factory(),
            'from_unit' => $this->faker->randomElement(['kg', 'g', 'l', 'ml', 'm', 'cm']),
            'to_unit' => $this->faker->randomElement(['kg', 'g', 'l', 'ml', 'm', 'cm']),
            'conversion_factor' => $this->faker->randomFloat(4, 0.0001, 1000),
        ];
    }
}
