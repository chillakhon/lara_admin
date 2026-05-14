<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;

class GenerateProductSlugs extends Command
{
    protected $signature = 'products:generate-slugs';
    protected $description = 'Generate slugs for existing products using transliteration';

    public function handle()
    {
        $products = Product::all();

        foreach ($products as $product) {
            $product->slug = $product->generateUniqueSlug($product->name);
            $product->save();
        }

        $this->info('Slugs generated for ' . $products->count() . ' products.');
    }
}
