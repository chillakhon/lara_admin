<?php

namespace Database\Factories;

use App\Models\Material;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InventoryBalance>
 */
class InventoryBalanceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'item_type' => Material::class,
            'item_id' => Material::factory(),
            'total_quantity' => $this->faker->randomFloat(3, 0, 10000),
            'average_price' => $this->faker->randomFloat(2, 1, 100),
            'unit_id' => Unit::factory(),
        ];
    }
}
