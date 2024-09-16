<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductSize;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductSize>
 */
class ProductSizeFactory extends Factory
{
    protected $model = ProductSize::class;

    public function definition()
    {
        return [
            'product_id' => Product::factory(),
            'name' => function (array $attributes) {
                $product = Product::find($attributes['product_id']);
                $dimensions = $this->getDimensionsForProduct($product->name);
                return $dimensions['width'] . 'x' . $dimensions['depth'] . ($dimensions['height'] ? 'x' . $dimensions['height'] : '');
            },
        ];
    }

    private function getDimensionsForProduct($productName)
    {
        $baseWidths = [
            'Диван' => [180, 200, 220],
            'Кровать' => [140, 160, 180],
            'Стол' => [120, 140, 160],
            'Стул' => [40, 45, 50],
            'Шкаф' => [80, 100, 120],
            'Комод' => [80, 100, 120],
            'Полка' => [60, 80, 100],
        ];

        $productType = explode(' ', $productName)[0];
        $baseWidth = $this->faker->randomElement($baseWidths[$productType] ?? [100, 120, 140]);

        $dimensions = [
            'width' => $baseWidth,
            'depth' => round($baseWidth * $this->faker->randomFloat(2, 0.4, 0.8)),
        ];

        // Добавляем высоту для определенных типов мебели
        if (in_array($productType, ['Шкаф', 'Комод', 'Стул'])) {
            $dimensions['height'] = round($baseWidth * $this->faker->randomFloat(2, 1.2, 2));
        }

        return $dimensions;
    }
}
