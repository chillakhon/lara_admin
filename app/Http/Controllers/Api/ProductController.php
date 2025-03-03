<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Http\Resources\ProductResource;
use App\Http\Resources\ProductDetailResource;
use Illuminate\Http\Request;

/**
 * @OA\Info(title="My API", version="1.0")
 * @OA\Server(url="http://localhost/api")
 */
class ProductController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/products",
     *     summary="Get list of products",
     *     @OA\Response(
     *         response=200,
     *         description="List of products"
     *     )
     * )
     */
    public function index2()
    {
        // Логика для получения списка продуктов
        echo 'dd';
    }

    public function index(Request $request)
    {
        $query = Product::query()
            ->with([
                'categories',
                'defaultUnit',
                'activeVariants.optionValues.option',
                'activeVariants.images',
                'activeVariants.unit',
                'options.values'
            ])
            ->where('is_active', true);

        // Фильтрация по категории
        if ($request->has('category')) {
            $query->whereHas('categories', function($q) use ($request) {
                $q->where('categories.id', $request->category);
            });
        }

        // Поиск по названию или описанию
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Сортировка
        $sortField = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');
        $allowedSortFields = ['created_at', 'name', 'price'];

        if (in_array($sortField, $allowedSortFields)) {
            $query->orderBy($sortField, $sortDirection);
        }

        $perPage = $request->get('per_page', 12);
        $products = $query->paginate($perPage);

        return ProductResource::collection($products);
    }

    public function show($slug)
    {
        $product = Product::where('slug', $slug)
            ->where('is_active', true)
            ->with([
                'categories',
                'defaultUnit',
                'activeVariants' => function($query) {
                    $query->with(['optionValues.option', 'unit', 'images']);
                },
                'options' => function($query) {
                    $query->with('values')->orderByPivot('order');
                }
            ])
            ->firstOrFail();

        return new ProductDetailResource($product);
    }
}
