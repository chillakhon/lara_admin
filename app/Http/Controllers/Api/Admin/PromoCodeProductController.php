<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\PromoCodeProduct;
use Illuminate\Http\Request;
use Laravel\Reverb\Loggers\Log;

class PromoCodeProductController extends Controller
{
    public function index()
    {
        $promoCodeProducts = PromoCodeProduct::with(['promoCode', 'product'])->get();

        return response()->json([
            'success' => true,
            'data' => $promoCodeProducts,
        ]);
    }


    public function getProductsByPromoCode(Request $request, string $promoCodeId)
    {
        $withVariants = $request->boolean('withVariants');

        $promoProducts = PromoCodeProduct::with(['product', 'productVariant'])
            ->where('promo_code_id', $promoCodeId)
            ->get();

        if ($withVariants) {
            $products = $promoProducts
                ->groupBy('product_id')
                ->map(function ($items) {
                    $product = $items->first()->product;

                    $variants = $items->map(fn($item) => $item->productVariant)
                        ->filter()
                        ->values();

                    $product->variants = $variants;

                    return $product;
                })->values();
        } else {
            $products = $promoProducts->pluck('product')->unique('id')->values();
        }


        return response()->json([
            'success' => true,
            'data' => $products,
        ]);
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'promo_code_id' => 'required|exists:promo_codes,id',
            'product_id' => 'required|exists:products,id',
        ]);

        $promoCodeProduct = PromoCodeProduct::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Продукт успешно привязан к промокоду',
            'data' => $promoCodeProduct->load(['promoCode', 'product']),
        ]);
    }

    public function show(string $id)
    {
        $promoCodeProduct = PromoCodeProduct::with(['promoCode', 'product'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $promoCodeProduct,
        ]);
    }

    public function update(Request $request, string $id)
    {
        $promoCodeProduct = PromoCodeProduct::findOrFail($id);

        $validated = $request->validate([
            'promo_code_id' => 'required|exists:promo_codes,id',
            'product_id' => 'required|exists:products,id',
        ]);

        $promoCodeProduct->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Запись обновлена',
            'data' => $promoCodeProduct->load(['promoCode', 'product']),
        ]);
    }

    public function destroy(string $id)
    {
        $promoCodeProduct = PromoCodeProduct::findOrFail($id);
        $promoCodeProduct->delete();

        return response()->json([
            'success' => true,
            'message' => 'Запись удалена',
        ]);
    }
}
