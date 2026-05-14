<?php

namespace Database\Factories;

use App\Models\ColorOptionValue;
use App\Models\Product;
use App\Models\ProductSize;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    protected $model = ProductVariant::class;

    public function definition()
    {
        return [
            'product_id' => Product::factory(),
            'name' => function (array $attributes) {
                $product = Product::find($attributes['product_id']);
                $size = ProductSize::find($attributes['product_size_id']);
                $color = ColorOptionValue::find($attributes['color_option_value_id'])->color;
                return "{$product->name} - {$size->name} - {$color->title}";
            },
            'article' => $this->faker->unique()->ean8,
            'additional_cost' => $this->faker->randomFloat(2, 0, 5000),
            'price' => $this->faker->randomFloat(2, 1000, 50000),
            'stock_quantity' => $this->faker->numberBetween(0, 50),
            'product_size_id' => ProductSize::factory(),
            'color_option_value_id' => ColorOptionValue::factory(),
        ];
    }
}

