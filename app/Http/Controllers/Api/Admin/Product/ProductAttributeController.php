<?php

namespace App\Http\Controllers\Api\Admin\Product;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\Product\ProductAttributeService;
use Illuminate\Http\Request;

class ProductAttributeController extends Controller
{
    protected ProductAttributeService $attributeService;

    public function __construct(ProductAttributeService $attributeService)
    {
        $this->attributeService = $attributeService;
    }

    /**
     * Обновить уровень впитываемости
     *
     * PATCH /api/admin/products/{product}/absorbency
     *
     * Body:
     * {
     *   "absorbency_level": 3
     * }
     */
    public function updateAbsorbency(Request $request, Product $product)
    {
        $validated = $request->validate([
            'absorbency_level' => 'required|integer|min:0|max:6',
        ]);

        $result = $this->attributeService->updateAbsorbency(
            $product->id,
            $validated['absorbency_level']
        );

        $statusCode = $result['success'] ? 200 : 422;
        return response()->json($result, $statusCode);
    }

    /**
     * Обновить несколько характеристик сразу
     *
     * PATCH /api/admin/products/{product}/attributes
     *
     * Body:
     * {
     *   "absorbency_level": 4,
     *   "weight": 50,
     *   "color": "white"
     * }
     */
    public function updateAttributes(Request $request, Product $product)
    {
        $validated = $request->validate([
            'absorbency_level' => 'integer|min:0|max:6',
            'weight' => 'numeric|nullable',
            'color' => 'string|nullable',
            // добавляй другие характеристики по мере необходимости
        ]);

        // Фильтруем пустые значения
        $attributes = array_filter($validated, fn($value) => $value !== null);

        $result = $this->attributeService->updateAttributes($product->id, $attributes);

        $statusCode = $result['success'] ? 200 : 422;
        return response()->json($result, $statusCode);
    }
}
