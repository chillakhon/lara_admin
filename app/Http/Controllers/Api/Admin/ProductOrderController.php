<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\Product\ProductOrderService;
use Illuminate\Http\Request;

class ProductOrderController extends Controller
{
    protected ProductOrderService $productOrderService;

    public function __construct(ProductOrderService $productOrderService)
    {
        $this->productOrderService = $productOrderService;
    }

    /**
     * Изменить порядок конкретного товара
     *
     * POST /api/admin/products/{id}/order
     */
    public function updateOrder(Request $request, Product $product)
    {
        $validated = $request->validate([
            'display_order' => 'required|integer|min:1',
        ]);

        $result = $this->productOrderService->updateProductOrder(
            $product->id,
            $validated['display_order']
        );

        if ($result['success']) {
            return response()->json($result, 200);
        }

        return response()->json($result, 422);
    }

    /**
     * Получить максимальный порядок
     *
     * GET /api/admin/products/order/max
     */
    public function getMaxOrder()
    {
        $maxOrder = $this->productOrderService->getMaxOrder();

        return response()->json([
            'success' => true,
            'max_order' => $maxOrder,
            'next_order' => $maxOrder + 1
        ], 200);
    }

    /**
     * Инициализировать порядок для всех товаров
     * Использовать только один раз для старых баз данных
     *
     * POST /api/admin/products/order/initialize
     */
    public function initializeOrders()
    {
        $result = $this->productOrderService->initializeAllProductOrders();

        if ($result['success']) {
            return response()->json($result, 200);
        }

        return response()->json($result, 500);
    }

    /**
     * Пакетное обновление порядка товаров
     *
     * POST /api/admin/products/order/bulk-update
     *
     * Body:
     * {
     *   "orders": {
     *     "1": 5,
     *     "2": 1,
     *     "3": 2
     *   }
     * }
     */
    public function bulkUpdateOrders(Request $request)
    {
        $validated = $request->validate([
            'orders' => 'required|array',
            'orders.*' => 'integer|min:1',
        ]);

        $result = $this->productOrderService->bulkUpdateOrders($validated['orders']);

        if ($result['success']) {
            return response()->json($result, 200);
        }

        return response()->json($result, 422);
    }

    /**
     * Перестроить порядок товаров (убрать пробелы)
     *
     * POST /api/admin/products/order/rebuild
     */
    public function rebuildOrders()
    {
        $result = $this->productOrderService->rebuildProductOrders();

        if ($result['success']) {
            return response()->json($result, 200);
        }

        return response()->json($result, 500);
    }

    /**
     * Получить список всех товаров с их порядком
     *
     * GET /api/admin/products/order/list
     */
    public function getOrderedProducts(Request $request)
    {
        $products = Product::where('is_active', true)
            ->whereNull('deleted_at')
            ->orderBy('display_order', 'asc')
            ->select(['id', 'name', 'slug', 'display_order'])
            ->paginate($request->get('per_page', 25));

        return response()->json([
            'success' => true,
            'data' => $products
        ], 200);
    }
}
