<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductVariantController extends Controller
{
    public function store(Request $request, Product $product)
    {
        $request->validate([
            'size_name' => 'required|string',
            'base_price' => 'required|numeric|min:0',
        ]);

        // Создаем базовый вариант
        $baseVariant = $product->variants()->create([
            'name' => $request->size_name . ' - Base',
            'article' => $product->name . $request->size_name,
            'price' => $request->base_price,
            'stock' => 0, // Вы можете изменить это значение по умолчанию
        ]);

        // Создаем дополнительные варианты (например, с небольшими изменениями цены)
        $variants = [
            ['name' => $request->size_name . ' - Premium', 'price_modifier' => 1.1],
            ['name' => $request->size_name . ' - Deluxe', 'price_modifier' => 1.2],
        ];

        foreach ($variants as $variantData) {
            $product->variants()->create([
                'name' => $variantData['name'],
                'article' => Str::random(6),
                'price' => $request->base_price * $variantData['price_modifier'],
                'stock' => 0, // Вы можете изменить это значение по умолчанию
            ]);
        }

        return back()->with('success', 'Product variants created successfully.');
    }

    public function destroy(Product $product, ProductVariant $variant)
    {
        $variant->delete();
        return back()->with('success', 'Product variant deleted successfully.');
    }
}
