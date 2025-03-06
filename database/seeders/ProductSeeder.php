<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $products = [
            [
                'name' => 'Body AGAIN',
                'slug' => 'body-again',
                'description' => '16 вариантов',
                'type' => 'simple',
                'default_unit_id' => 1, // Укажите ID единицы измерения
                'is_active' => 1,
                'has_variants' => 1,
                'allow_preorder' => 0,
                'after_purchase_processing_time' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'BOX AGAIN',
                'slug' => 'box-again',
                'description' => '8 вариантов',
                'type' => 'simple',
                'default_unit_id' => 1,
                'is_active' => 1,
                'has_variants' => 1,
                'allow_preorder' => 0,
                'after_purchase_processing_time' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Love AGAIN',
                'slug' => 'love-again',
                'description' => '32 варианта',
                'type' => 'simple',
                'default_unit_id' => 1,
                'is_active' => 1,
                'has_variants' => 1,
                'allow_preorder' => 0,
                'after_purchase_processing_time' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'LOVE SET',
                'slug' => 'love-set',
                'description' => '8 вариантов',
                'type' => 'simple',
                'default_unit_id' => 1,
                'is_active' => 1,
                'has_variants' => 1,
                'allow_preorder' => 0,
                'after_purchase_processing_time' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Passion AGAIN',
                'slug' => 'passion-again',
                'description' => '8 вариантов',
                'type' => 'simple',
                'default_unit_id' => 1,
                'is_active' => 1,
                'has_variants' => 1,
                'allow_preorder' => 0,
                'after_purchase_processing_time' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Save AGAIN',
                'slug' => 'save-again',
                'description' => '8 вариантов',
                'type' => 'simple',
                'default_unit_id' => 1,
                'is_active' => 1,
                'has_variants' => 1,
                'allow_preorder' => 0,
                'after_purchase_processing_time' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Sexy AGAIN',
                'slug' => 'sexy-again',
                'description' => '8 вариантов',
                'type' => 'simple',
                'default_unit_id' => 1,
                'is_active' => 1,
                'has_variants' => 1,
                'allow_preorder' => 0,
                'after_purchase_processing_time' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Любимый SET от доктора Садовская',
                'slug' => 'set-love-doctor',
                'description' => '8 вариантов',
                'type' => 'simple',
                'default_unit_id' => 1,
                'is_active' => 1,
                'has_variants' => 1,
                'allow_preorder' => 0,
                'after_purchase_processing_time' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('products')->insert($products);
    }
}
