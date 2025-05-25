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
        $products = collect([
            [
                'name' => 'Body AGAIN',
                'slug' => 'body-again',
                'description' => '16 вариантов',
            ],
            [
                'name' => 'BOX AGAIN',
                'slug' => 'box-again',
                'description' => '8 вариантов',
            ],
            [
                'name' => 'Love AGAIN',
                'slug' => 'love-again',
                'description' => '32 варианта',
            ],
            [
                'name' => 'LOVE SET',
                'slug' => 'love-set',
                'description' => '8 вариантов',
            ],
            [
                'name' => 'Passion AGAIN',
                'slug' => 'passion-again',
                'description' => '8 вариантов',
            ],
            [
                'name' => 'Save AGAIN',
                'slug' => 'save-again',
                'description' => '8 вариантов',
            ],
            [
                'name' => 'Sexy AGAIN',
                'slug' => 'sexy-again',
                'description' => '8 вариантов',
            ],
            [
                'name' => 'Любимый SET от доктора Садовская',
                'slug' => 'set-love-doctor',
                'description' => '8 вариантов',
            ],
        ])->map(function ($product) {
            return array_merge($product, [
                'type' => 'simple',
                'default_unit_id' => 1,
                'is_active' => 1,
                'has_variants' => 1,
                'allow_preorder' => 0,
                'after_purchase_processing_time' => 0,
                'weight' => rand(100, 1000) / 10, // weight in grams or kg (10.0 to 100.0)
                'length' => rand(5, 50), // cm
                'width' => rand(5, 50),  // cm
                'height' => rand(5, 50), // cm
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        })->toArray();

        DB::table('products')->insert($products);
    }
}
