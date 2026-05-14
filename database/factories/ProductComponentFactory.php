<?php

namespace Database\Factories;

use App\Models\Material;
use App\Models\Product;
use App\Models\ProductComponent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductComponent>
 */
class ProductComponentFactory extends Factory
{
    protected $model = ProductComponent::class;

    public function definition()
    {
        return [
            'product_id' => Product::factory(),
            'material_id' => Material::factory(),
            'quantity' => $this->faker->randomFloat(2, 0.1, 10),
        ];
    }
}
