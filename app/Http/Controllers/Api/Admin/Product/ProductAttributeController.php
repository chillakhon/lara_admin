<?php

namespace App\Http\Controllers\Api\Admin\Product;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\Attribute\BulkUpdateProductAttributesRequest;
use App\Http\Requests\Product\Attribute\UpdateProductAttributesRequest;
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
     * @param UpdateProductAttributesRequest $request
     * @param Product $product
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateAttributes(UpdateProductAttributesRequest $request, Product $product)
    {
        $validated = $request->validated();

        // Фильтруем пустые значения
        $attributes = array_filter($validated, fn($value) => $value !== null);

        $result = $this->attributeService->updateAttributes($product->id, $attributes);

        $statusCode = $result['success'] ? 200 : 422;
        return response()->json($result, $statusCode);
    }


    /**
     * @param BulkUpdateProductAttributesRequest $request
     * @return \Illuminate\Http\JsonResponse
     */

    public function bulkUpdateAttributes(BulkUpdateProductAttributesRequest $request)
    {
        $validated = $request->validated();

        // Фильтруем пустые значения
        $attributes = array_filter(
            $validated['attributes'],
            fn($value) => $value !== null
        );

        $result = $this->attributeService->bulkUpdateAttributes(
            $validated['product_ids'],
            $attributes
        );

        $statusCode = $result['success'] ? 200 : 422;
        return response()->json($result, $statusCode);
    }

}
