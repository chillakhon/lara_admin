<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Image;
use App\Models\Product;
use App\Services\MaterialService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    protected $materialService;

    public function __construct(MaterialService $materialService)
    {
        $this->materialService = $materialService;
    }

    /**
     * @OA\Get(
     *     path="/api/products",
     *     summary="Получить список продуктов",
     *     description="Возвращает список продуктов с фильтрацией по названию, описанию и категориям.",
     *     operationId="getProducts",
     *     tags={"Products"},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Поиск по названию, описанию или категории",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="category",
     *         in="query",
     *         description="Фильтр по категории (ID категории)",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Список продуктов",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="current_page", type="integer"),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Product Name"),
     *                     @OA\Property(property="description", type="string", example="Product Description"),
     *                     @OA\Property(property="categories", type="array",
     *                         @OA\Items(
     *                             type="object",
     *                             @OA\Property(property="id", type="integer", example=1),
     *                             @OA\Property(property="name", type="string", example="Category Name")
     *                         )
     *                     ),
     *                     @OA\Property(property="options", type="array", @OA\Items(type="object")),
     *                     @OA\Property(property="variants", type="array", @OA\Items(type="object")),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time")
     *                 )
     *             ),
     *             @OA\Property(property="per_page", type="integer", example=10),
     *             @OA\Property(property="total", type="integer", example=100)
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $products = Product::with(['categories', 'options', 'variants', 'images'])
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


        $products->getCollection()->transform(function ($product) {
            $product->image_path = $product->images->isNotEmpty() ? $product->images->first()->path : null;
            unset($product->images);
            return $product;
        });

        return response()->json($products);
    }

    /**
     * @OA\Get(
     *     path="/api/products/{product}",
     *     summary="Получить информацию о продукте",
     *     description="Возвращает детальную информацию о продукте с категориями, вариантами и опциями.",
     *     operationId="getProduct",
     *     tags={"Products"},
     *     @OA\Parameter(
     *         name="product",
     *         in="path",
     *         description="ID продукта",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Детальная информация о продукте",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="Product Name"),
     *             @OA\Property(property="description", type="string", example="Product Description"),
     *             @OA\Property(property="defaultUnit", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Unit Name")
     *             ),
     *             @OA\Property(property="categories", type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Category Name")
     *                 )
     *             ),
     *             @OA\Property(property="options", type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Option Name"),
     *                     @OA\Property(property="values", type="array",
     *                         @OA\Items(
     *                             type="object",
     *                             @OA\Property(property="id", type="integer", example=1),
     *                             @OA\Property(property="value", type="string", example="Red")
     *                         )
     *                     )
     *                 )
     *             ),
     *             @OA\Property(property="variants", type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=10),
     *                     @OA\Property(property="optionValues", type="array",
     *                         @OA\Items(
     *                             type="object",
     *                             @OA\Property(property="id", type="integer", example=1),
     *                             @OA\Property(property="value", type="string", example="Large"),
     *                             @OA\Property(property="option", type="object",
     *                                 @OA\Property(property="id", type="integer", example=1),
     *                                 @OA\Property(property="name", type="string", example="Size")
     *                             )
     *                         )
     *                     ),
     *                     @OA\Property(property="images", type="array",
     *                         @OA\Items(
     *                             type="object",
     *                             @OA\Property(property="url", type="string", example="https://example.com/image.jpg")
     *                         )
     *                     ),
     *                     @OA\Property(property="unit", type="object",
     *                         @OA\Property(property="id", type="integer", example=2),
     *                         @OA\Property(property="name", type="string", example="Kilogram")
     *                     )
     *                 )
     *             ),
     *             @OA\Property(property="created_at", type="string", format="date-time"),
     *             @OA\Property(property="updated_at", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Продукт не найден",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Product not found")
     *         )
     *     )
     * )
     */

    public function show(Product $product)
    {
        $product->load([
            'images',
            'options.values',
            'variants.optionValues.option',
            'variants.images',
            'variants.unit',
            'defaultUnit'
        ]);
        return response()->json($product);
    }


    // enhances-dev branch
    public function price_history(Request $request, Product $product)
    {
        // TODO: logic for getting product's prices history
    }



    // enhances-dev branch
    public function warehouse_history(Request $request, Product $product)
    {
        // TODO: logic for getting product's qty history from warehouse
    }

    /**
     * @OA\Post(
     *     path="/api/products",
     *     summary="Create a new product",
     *     tags={"Products"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "type", "price", "categories"},
     *             @OA\Property(property="name", type="string", example="Product Name"),
     *             @OA\Property(property="description", type="string", nullable=true, example="Product description"),
     *             @OA\Property(
     *                 property="type",
     *                 type="string",
     *                 enum={"simple", "manufactured", "composite"},
     *                 example="simple"
     *             ),
     *             @OA\Property(property="default_unit_id", type="integer", nullable=true, example=1),
     *             @OA\Property(property="is_active", type="boolean", example=true),
     *             @OA\Property(property="has_variants", type="boolean", example=false),
     *             @OA\Property(property="allow_preorder", type="boolean", example=true),
     *             @OA\Property(property="after_purchase_processing_time", type="integer", example=3),
     *             @OA\Property(property="price", type="number", format="float", example=99.99),
     *             @OA\Property(property="cost_price", type="number", format="float", nullable=true, example=50.00),
     *             @OA\Property(property="stock_quantity", type="integer", example=100),
     *             @OA\Property(property="min_order_quantity", type="integer", nullable=true, example=1),
     *             @OA\Property(property="max_order_quantity", type="integer", nullable=true, example=10),
     *             @OA\Property(property="is_featured", type="boolean", nullable=true, example=false),
     *             @OA\Property(property="is_new", type="boolean", nullable=true, example=true),
     *             @OA\Property(property="discount_price", type="number", format="float", nullable=true, example=89.99),
     *             @OA\Property(property="sku", type="string", nullable=true, example="SKU12345"),
     *             @OA\Property(property="barcode", type="string", nullable=true, example="123456789012"),
     *             @OA\Property(property="weight", type="number", format="float", nullable=true, example=1.5),
     *             @OA\Property(property="length", type="number", format="float", nullable=true, example=10.0),
     *             @OA\Property(property="width", type="number", format="float", nullable=true, example=5.0),
     *             @OA\Property(property="height", type="number", format="float", nullable=true, example=2.0),
     *             @OA\Property(
     *                 property="categories",
     *                 type="array",
     *                 @OA\Items(type="integer", example={1, 2})
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Product created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Product")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="name",
     *                     type="array",
     *                     @OA\Items(type="string", example="The name field is required.")
     *                 )
     *             )
     *         )
     *     )
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
            // Новые поля
            'price' => 'required|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'stock_quantity' => 'nullable|integer|min:0',
            'min_order_quantity' => 'nullable|integer|min:1',
            'max_order_quantity' => 'nullable|integer|min:1',
            'is_featured' => 'nullable|boolean',
            'is_new' => 'nullable|boolean',
            'discount_price' => 'nullable|numeric|min:0',
            'sku' => 'nullable|string|unique:products,sku',
            'barcode' => 'nullable|string|unique:products,barcode',
            'weight' => 'nullable|numeric|min:0',
            'length' => 'nullable|numeric|min:0',
            'width' => 'nullable|numeric|min:0',
            'height' => 'nullable|numeric|min:0',
        ]);

        $product = Product::create(array_merge($validated, ['slug' => Str::slug($validated['name'])]));
        $product->categories()->sync($validated['categories']);

        return response()->json([
            'message' => 'Product created successfully',
            'product' => $product], 201);
    }

    /**
     * @OA\Put(
     *     path="/api/products/{product}",
     *     summary="Обновить продукт",
     *     description="Обновляет существующий продукт по его ID.",
     *     operationId="updateProduct",
     *     tags={"Products"},
     *     @OA\Parameter(
     *         name="product",
     *         in="path",
     *         description="ID продукта",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Updated Product"),
     *             @OA\Property(property="description", type="string", nullable=true, example="Updated description"),
     *             @OA\Property(property="type", type="string", enum={"simple", "manufactured", "composite"}, example="manufactured"),
     *             @OA\Property(property="default_unit_id", type="integer", nullable=true, example=2),
     *             @OA\Property(property="is_active", type="boolean", example=true),
     *             @OA\Property(property="has_variants", type="boolean", example=true),
     *             @OA\Property(property="allow_preorder", type="boolean", example=true),
     *             @OA\Property(property="after_purchase_processing_time", type="integer", example=5),
     *             @OA\Property(property="categories", type="array",
     *                 @OA\Items(type="integer", example=2)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Продукт успешно обновлён",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Product updated successfully"),
     *             @OA\Property(property="product", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Updated Product"),
     *                 @OA\Property(property="slug", type="string", example="updated-product"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Продукт не найден",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Product not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Ошибка валидации"
     *     )
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
            'categories' => 'array',
            'categories.*' => 'exists:categories,id',
        ]);

        $product->update($validated);
        $product->categories()->sync($validated['categories']);

        return response()->json(['message' => 'Product updated successfully', 'product' => $product]);
    }

    /**
     * @OA\Delete(
     *     path="/api/products/{product}",
     *     summary="Удалить продукт",
     *     description="Удаляет продукт по его ID.",
     *     operationId="deleteProduct",
     *     tags={"Products"},
     *     @OA\Parameter(
     *         name="product",
     *         in="path",
     *         description="ID продукта",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Продукт успешно удалён",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Product deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Продукт не найден",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Product not found")
     *         )
     *     )
     * )
     */
    public function destroy(Product $product)
    {
        $product->delete();
        return response()->json(['message' => 'Product deleted successfully']);
    }

    public function storeImages(Request $request, Product $product)
    {
        $request->validate([
            'images' => 'required|array',
            'images.*' => 'required|image|max:5120',
        ]);

        $uploadedImages = [];
        foreach ($request->file('images') as $image) {
            $path = $image->store('products', 'public');
            $imageModel = $product->images()->create(['path' => $path, 'url' => Storage::url($path)]);
            $uploadedImages[] = $imageModel;
        }

        return response()->json(['message' => 'Images uploaded successfully', 'images' => $uploadedImages]);
    }

    /**
     * @OA\Post(
     *     path="/api/products/{product}/generate-variants",
     *     summary="Generate multiple variants for a product",
     *     tags={"Product Variants"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="product",
     *         in="path",
     *         description="ID of the product",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"variants"},
     *             @OA\Property(
     *                 property="variants",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     required={"name", "sku", "price", "option_values"},
     *                     @OA\Property(property="name", type="string", example="Variant 1"),
     *                     @OA\Property(property="sku", type="string", example="variant-1-unique-sku"),
     *                     @OA\Property(property="price", type="number", format="float", example=19.99),
     *                     @OA\Property(
     *                         property="option_values",
     *                         type="array",
     *                         @OA\Items(type="integer", example=1)
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Variants generated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Variants generated successfully"),
     *             @OA\Property(
     *                 property="variants",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Variant 1"),
     *                     @OA\Property(property="sku", type="string", example="variant-1-unique-sku"),
     *                     @OA\Property(property="price", type="number", format="float", example=19.99),
     *                     @OA\Property(property="type", type="string", example="simple"),
     *                     @OA\Property(property="unit_id", type="integer", example=1),
     *                     @OA\Property(property="is_active", type="boolean", example=true),
     *                     @OA\Property(
     *                         property="option_values",
     *                         type="array",
     *                         @OA\Items(
     *                             type="object",
     *                             @OA\Property(property="id", type="integer", example=1),
     *                             @OA\Property(property="name", type="string", example="Option Value 1")
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="variants.0.sku",
     *                     type="array",
     *                     @OA\Items(type="string", example="The variants.0.sku has already been taken.")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Failed to generate variants"),
     *             @OA\Property(property="error", type="string", example="Error message details")
     *         )
     *     )
     * )
     */
    public function generateVariants(Request $request, Product $product)
    {
        $validated = $request->validate([
            'variants' => 'required|array',
            'variants.*.name' => 'required|string',
            'variants.*.sku' => 'required|string|unique:product_variants,sku',
            'variants.*.price' => 'required|numeric|min:0',
            'variants.*.option_values' => 'required|array',
            'variants.*.option_values.*' => 'exists:option_values,id',
        ]);

        try {
            DB::beginTransaction();

            $createdVariants = [];

            foreach ($validated['variants'] as $variantData) {
                $variant = $product->variants()->create([
                    'name' => $variantData['name'],
                    'sku' => $variantData['sku'],
                    'price' => $variantData['price'],
                    'type' => $product->type,
                    'unit_id' => $product->default_unit_id,
                    'is_active' => true,
                ]);

                $variant->optionValues()->sync($variantData['option_values']);

                $createdVariants[] = $variant;
            }

            DB::commit();

            return response()->json([
                'message' => 'Variants generated successfully',
                'variants' => $createdVariants,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to generate variants',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * @OA\Post(
     *     path="/api/products/{product}/components",
     *     summary="Add a component to a product",
     *     tags={"Products"},
     *     @OA\Parameter(
     *         name="product",
     *         in="path",
     *         required=true,
     *         description="ID of the product",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"material_id", "quantity"},
     *             @OA\Property(property="material_id", type="integer", example=1),
     *             @OA\Property(property="quantity", type="number", example=5)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Component added successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Component added successfully."),
     *             @OA\Property(property="component", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function addComponent(Request $request, Product $product)
    {
        $validated = $request->validate([
            'material_id' => 'required|exists:materials,id',
            'quantity' => 'required|numeric|min:0',
        ]);

        $component = $product->components()->create($validated);

        return response()->json([
            'message' => 'Component added successfully.',
            'component' => $component
        ], 201);
    }

    /**
     * @OA\Delete(
     *     path="/api/products/{product}/components/{component}",
     *     summary="Remove a component from a product",
     *     tags={"Products"},
     *     @OA\Parameter(
     *         name="product",
     *         in="path",
     *         required=true,
     *         description="ID of the product",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="component",
     *         in="path",
     *         required=true,
     *         description="ID of the component",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Component removed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Component removed successfully.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Component not found"
     *     )
     * )
     */
    public function removeComponent(Product $product, $componentId)
    {
        $product->components()->findOrFail($componentId)->delete();

        return response()->json([
            'message' => 'Component removed successfully.'
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/products/{product}/calculate-cost",
     *     summary="Calculate the cost of a product",
     *     tags={"Products"},
     *     @OA\Parameter(
     *         name="product",
     *         in="path",
     *         required=true,
     *         description="ID of the product",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Calculated product cost",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The calculated cost is: 100"),
     *             @OA\Property(property="cost", type="number", example=100)
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Product not found"
     *     )
     * )
     */
    public function calculateCost(Product $product)
    {
        $cost = $this->materialService->calculateProductCost($product);

        return response()->json([
            'message' => "The calculated cost is: $cost",
            'cost' => $cost
        ], 200);
    }

}
