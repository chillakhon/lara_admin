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
        $this->call([
            UnitsTableSeeder::class,
            // другие сидеры...
        ]);
    }
}
