<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Product;
use App\Models\ColorCategory;
use App\Models\Color;
use App\Models\Material;
use App\Models\ColorOption;
use App\Models\ColorOptionValue;
use App\Models\ProductSize;
use App\Models\ProductVariant;
use App\Models\ProductComponent;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // Создаем основные категории
        $mainCategories = [
            'Гостиная', 'Спальня', 'Кухня', 'Офис', 'Детская', 'Ванная'
        ];

        foreach ($mainCategories as $categoryName) {
            Category::create([
                'name' => $categoryName,
                'slug' => Str::slug($categoryName),
                'description' => "Мебель для {$categoryName}",
            ]);
        }

        // Создаем подкатегории
        $subCategories = [
            'Диваны', 'Кровати', 'Столы', 'Стулья', 'Шкафы', 'Комоды', 'Полки'
        ];

        Category::all()->each(function ($category) use ($subCategories) {
            foreach ($subCategories as $subCategoryName) {
                Category::create([
                    'name' => $subCategoryName,
                    'slug' => Str::slug($subCategoryName),
                    'description' => "{$subCategoryName} для {$category->name}",
                    'parent_id' => $category->id,
                ]);
            }
        });

        // Создаем цветовые категории
        $colorCategories = ['Светлые', 'Темные', 'Яркие'];
        foreach ($colorCategories as $colorCategoryName) {
            ColorCategory::create(['title' => $colorCategoryName]);
        }

        // Создаем цвета
        $colors = [
            'Белый' => ['FFFFFF', 'Светлые'],
            'Черный' => ['000000', 'Темные'],
            'Серый' => ['808080', 'Светлые'],
            'Коричневый' => ['8B4513', 'Темные'],
            'Бежевый' => ['F5F5DC', 'Светлые'],
            'Синий' => ['0000FF', 'Яркие'],
            'Зеленый' => ['008000', 'Яркие'],
            'Красный' => ['FF0000', 'Яркие'],
        ];

        foreach ($colors as $colorName => $colorInfo) {
            Color::create([
                'title' => $colorName,
                'code' => $colorInfo[0],
                'color_category_id' => ColorCategory::where('title', $colorInfo[1])->first()->id,
            ]);
        }

        // Создаем материалы
        $materials = [
            'Дуб' => 'м²',
            'Сосна' => 'м²',
            'МДФ' => 'м²',
            'ДСП' => 'м²',
            'Кожа' => 'м²',
            'Ткань' => 'м²',
            'Металл' => 'кг',
            'Стекло' => 'м²',
            'Пластик' => 'кг',
        ];

        foreach ($materials as $materialName => $unit) {
            Material::create([
                'title' => $materialName,
                'unit_of_measurement' => $unit,
                'cost_per_unit' => rand(10, 1000) / 10,
            ]);
        }

        // Создаем продукты
        $products = [
            'Диван "Комфорт"' => 'Диваны',
            'Кровать "Сон"' => 'Кровати',
            'Стол "Работяга"' => 'Столы',
            'Стул "Эргономик"' => 'Стулья',
            'Шкаф "Вместительный"' => 'Шкафы',
            'Комод "Организатор"' => 'Комоды',
            'Полка "Воздушная"' => 'Полки',
        ];

        foreach ($products as $productName => $subcategoryName) {
            $product = Product::create([
                'name' => $productName,
                'description' => "Отличный выбор для вашего дома",
                'is_available' => true,
            ]);

            // Привязываем продукт к случайной подкатегории
            $subcategory = Category::where('name', $subcategoryName)->inRandomOrder()->first();
            $product->categories()->attach($subcategory->id);

            // Создаем цветовые опции для продукта
            $colorOption = ColorOption::create([
                'title' => 'Цвет',
                'product_id' => $product->id,
                'category_id' => $subcategory->id
            ]);

            // Создаем значения цветовых опций
            $productColors = Color::inRandomOrder()->limit(3)->get();
            foreach ($productColors as $color) {
                ColorOptionValue::create([
                    'color_id' => $color->id,
                    'product_id' => $product->id,
                    'color_option_id' => $colorOption->id
                ]);
            }

            // Создаем размеры для продукта
            $sizesCount = rand(1, 3);
            $sizes = [];
            for ($i = 0; $i < $sizesCount; $i++) {
                $dimensions = $this->getDimensionsForProduct($subcategoryName);
                $sizes[] = $dimensions['width'] . 'x' . $dimensions['depth'] .
                    (isset($dimensions['height']) ? 'x' . $dimensions['height'] : '');
            }

            // Создаем варианты продукта
            foreach ($sizes as $size) {
                foreach ($productColors as $color) {
                    $colorOptionValue = ColorOptionValue::where('product_id', $product->id)
                        ->where('color_id', $color->id)
                        ->first();

                    ProductVariant::create([
                        'product_id' => $product->id,
                        'name' => "{$product->name} - {$size} - {$color->title}",
                        'article' => Str::random(8),
                        'additional_cost' => rand(0, 5000),
                        'price' => rand(1000, 50000),
                        'stock' => rand(0, 50),
                        'color_option_value_id' => $colorOptionValue->id,
                    ]);
                }
            }

            // Создаем компоненты продукта
            $productMaterials = Material::inRandomOrder()->limit(3)->get();
            foreach ($productMaterials as $material) {
                ProductComponent::create([
                    'product_id' => $product->id,
                    'material_id' => $material->id,
                    'quantity' => rand(1, 10) + rand(0, 99) / 100,
                ]);
            }
        }
    }

    private function getDimensionsForProduct($productType)
    {
        $baseWidths = [
            'Диваны' => [180, 200, 220],
            'Кровати' => [140, 160, 180],
            'Столы' => [120, 140, 160],
            'Стулья' => [40, 45, 50],
            'Шкафы' => [80, 100, 120],
            'Комоды' => [80, 100, 120],
            'Полки' => [60, 80, 100],
        ];

        $baseWidth = $baseWidths[$productType][array_rand($baseWidths[$productType])];

        $dimensions = [
            'width' => $baseWidth,
            'depth' => round($baseWidth * (rand(40, 80) / 100)),
        ];

        // Добавляем высоту только для определенных типов мебели
        if (in_array($productType, ['Шкафы', 'Комоды', 'Стулья'])) {
            $dimensions['height'] = round($baseWidth * (rand(120, 200) / 100));
        }

        return $dimensions;
    }
}
