<?php

namespace Database\Seeders;

use App\Models\ProductVariant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductVariantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Добавляем продукты
        $products = [
            [
                'name' => 'Test E BA Body AGAIN',
                'slug' => 'test-e-body-again_1',
                'description' => '16 вариантов',
                'type' => 'simple',
                'default_unit_id' => 1, // Укажите ID единицы измерения
                'is_active' => 1,
                'has_variants' => 1,
                'allow_preorder' => 0,
                'after_purchase_processing_time' => 0,
                'created_at' => now(),
                'updated_at' => now(),
                'weight' => rand(100, 1000) / 10, // weight in grams or kg (10.0 to 100.0)
                'length' => rand(5, 50), // cm
                'width' => rand(5, 50),  // cm
                'height' => rand(5, 50), // cm
            ],
            // Добавьте другие продукты, если нужно
        ];

        // Вставляем продукты в таблицу products
        DB::table('products')->insert($products);

        // Получаем ID добавленных продуктов
        $bodyAgainId = DB::table('products')->where('slug', 'body-again')->first()->id;

        // Добавляем варианты для продукта Body AGAIN
        $variants = [
            [
                'product_id' => $bodyAgainId,
                'name' => 'XS / Черный',
                'sku' => 'again-body-черный-xs',
                'price' => 2490.00,
                'additional_cost' => 0.00,
                'type' => 'simple',
                'unit_id' => 1,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'product_id' => $bodyAgainId,
                'name' => 'XS / Фиолет',
                'sku' => 'again-body-фиолет-xs',
                'price' => 2490.00,
                'additional_cost' => 0.00,
                'type' => 'simple',
                'unit_id' => 1,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'product_id' => $bodyAgainId,
                'name' => 'S / Черный',
                'sku' => 'again-body-черный-s',
                'price' => 2490.00,
                'additional_cost' => 0.00,
                'type' => 'simple',
                'unit_id' => 1,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'product_id' => $bodyAgainId,
                'name' => 'S / Фиолет',
                'sku' => 'again-body-фиолет-s',
                'price' => 2490.00,
                'additional_cost' => 0.00,
                'type' => 'simple',
                'unit_id' => 1,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'product_id' => $bodyAgainId,
                'name' => 'M / Черный',
                'sku' => 'again-body-черный-m',
                'price' => 2490.00,
                'additional_cost' => 0.00,
                'type' => 'simple',
                'unit_id' => 1,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'product_id' => $bodyAgainId,
                'name' => 'M / Фиолет',
                'sku' => 'again-body-фиолет-m',
                'price' => 2490.00,
                'additional_cost' => 0.00,
                'type' => 'simple',
                'unit_id' => 1,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'product_id' => $bodyAgainId,
                'name' => 'L / Черный',
                'sku' => 'again-body-черный-l',
                'price' => 2490.00,
                'additional_cost' => 0.00,
                'type' => 'simple',
                'unit_id' => 1,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        // Вставляем варианты в таблицу product_variants
        DB::table('product_variants')->insert($variants);

        // Получаем ID добавленных вариантов
        $variantIds = DB::table('product_variants')->where('product_id', $bodyAgainId)->pluck('id');

        // Добавляем остатки на складе для каждого варианта
        $inventory = [
            [
                'item_type' => ProductVariant::class,
                'item_id' => $variantIds[0], // XS / Черный
                'total_quantity' => 9,
                'average_price' => 2490.00,
                'unit_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'item_type' => ProductVariant::class,
                'item_id' => $variantIds[1], // XS / Фиолет
                'total_quantity' => 20,
                'average_price' => 2490.00,
                'unit_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'item_type' => ProductVariant::class,
                'item_id' => $variantIds[2], // S / Черный
                'total_quantity' => 0,
                'average_price' => 2490.00,
                'unit_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'item_type' => ProductVariant::class,
                'item_id' => $variantIds[3], // S / Фиолет
                'total_quantity' => 18,
                'average_price' => 2490.00,
                'unit_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'item_type' => ProductVariant::class,
                'item_id' => $variantIds[4], // M / Черный
                'total_quantity' => 0,
                'average_price' => 2490.00,
                'unit_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'item_type' => ProductVariant::class,
                'item_id' => $variantIds[5], // M / Фиолет
                'total_quantity' => 20,
                'average_price' => 2490.00,
                'unit_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'item_type' => ProductVariant::class,
                'item_id' => $variantIds[6], // L / Черный
                'total_quantity' => 0,
                'average_price' => 2490.00,
                'unit_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        // Вставляем остатки в таблицу inventory_balances
        DB::table('inventory_balances')->insert($inventory);
    }
}
