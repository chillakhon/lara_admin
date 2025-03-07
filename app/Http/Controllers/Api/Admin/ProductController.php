<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Image;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Unit;
use App\Services\MaterialService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * @OA\Tag(name="Products", description="API для управления товарами")
 */
class ProductController extends Controller
{
    protected $materialService;

    public function __construct(MaterialService $materialService)
    {
        $this->materialService = $materialService;
    }

    /**
     * @OA\Get(
     *     path="/api/admin/products",
     *     summary="Получение списка товаров",
     *     tags={"Products"},
     *     @OA\Parameter(name="search", in="query", description="Поиск по названию и описанию", @OA\Schema(type="string")),
     *     @OA\Parameter(name="category", in="query", description="Фильтр по категории", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Список товаров")
     * )
     */
    public function index(Request $request)
    {
        $products = Product::with(['categories', 'options', 'variants'])
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('categories', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            })
            ->when($request->category, function ($query, $categoryId) {
                $query->whereHas('categories', function ($q) use ($categoryId) {
                    $q->where('categories.id', $categoryId);
                });
            })
            ->latest()
            ->paginate(10);

        return response()->json($products);
    }

    /**
     * @OA\Get(
     *     path="/api/admin/products/{id}",
     *     summary="Получение информации о товаре",
     *     tags={"Products"},
     *     @OA\Parameter(name="id", in="path", required=true, description="ID товара", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Данные товара")
     * )
     */
    public function show(Product $product)
    {
        $product->load(['categories', 'options.values', 'variants.optionValues.option', 'variants.images', 'variants.unit', 'defaultUnit']);
        return response()->json($product);
    }

    /**
     * @OA\Post(
     *     path="/api/admin/products",
     *     summary="Создание нового товара",
     *     tags={"Products"},
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/Product")),
     *     @OA\Response(response=201, description="Товар создан")
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:simple,manufactured,composite',
            'default_unit_id' => 'nullable|exists:units,id',
            'is_active' => 'boolean',
            'has_variants' => 'boolean',
            'allow_preorder' => 'boolean',
            'after_purchase_processing_time' => 'integer|min:0',
            'categories' => 'required|array',
            'categories.*' => 'exists:categories,id',
        ]);

        $product = Product::create(array_merge($validated, ['slug' => Str::slug($validated['name'])]));
        $product->categories()->sync($validated['categories']);

        return response()->json(['message' => 'Product created successfully', 'product' => $product], 201);
    }

    /**
     * @OA\Put(
     *     path="/api/admin/products/{id}",
     *     summary="Обновление товара",
     *     tags={"Products"},
     *     @OA\Parameter(name="id", in="path", required=true, description="ID товара", @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/Product")),
     *     @OA\Response(response=200, description="Товар обновлен")
     * )
     */
    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:simple,manufactured,composite',
            'default_unit_id' => 'nullable|exists:units,id',
            'is_active' => 'boolean',
            'has_variants' => 'boolean',
            'allow_preorder' => 'boolean',
            'after_purchase_processing_time' => 'integer|min:0',
            'categories' => 'required|array',
            'categories.*' => 'exists:categories,id',
        ]);

        $product->update($validated);
        $product->categories()->sync($validated['categories']);

        return response()->json(['message' => 'Product updated successfully', 'product' => $product]);
    }

    /**
     * @OA\Delete(
     *     path="/api/admin/products/{id}",
     *     summary="Удаление товара",
     *     tags={"Products"},
     *     @OA\Parameter(name="id", in="path", required=true, description="ID товара", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Товар удален")
     * )
     */
    public function destroy(Product $product)
    {
        $product->delete();
        return response()->json(['message' => 'Product deleted successfully']);
    }
}
