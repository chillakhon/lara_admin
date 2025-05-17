<?php

namespace Database\Seeders;

use App\Models\Color;
use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductsColorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $first_product = Product::with('variants')->first();

        $colors = Color::limit(6)->get();

        foreach ($colors as $color) {
            $first_product->colors()->attach($color->id);
        }

        foreach ($first_product->variants as $value) {
            $colors = Color::limit(rand(1, 6))->get();

            foreach ($colors as $color) {
                $value->colors()->attach($color->id);
            }
        }
    }
}
