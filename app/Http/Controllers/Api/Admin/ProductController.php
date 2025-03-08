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

    public function show(Product $product)
    {
        $product->load(['categories', 'options.values', 'variants.optionValues.option', 'variants.images', 'variants.unit', 'defaultUnit']);
        return response()->json($product);
    }

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

        return response()->json([
            'message' => 'Product created successfully',
            'product' => $product], 201);
    }

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
}
