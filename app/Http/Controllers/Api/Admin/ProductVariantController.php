<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Image;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;

/**
 * @OA\Info(title="Product Variants API", version="1.0.0")
 */
class ProductVariantController extends Controller
{
    protected ImageService $imageService;

    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }

    /**
     * @OA\Post(
     *     path="/products/{product}/variants",
     *     summary="Create a new product variant",
     *     description="Create a new product variant for a given product.",
     *     tags={"Product Variants"},
     *     @OA\Parameter(
     *         name="product",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 type="object",
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="sku", type="string"),
     *                 @OA\Property(property="price", type="number", format="float"),
     *                 @OA\Property(property="additional_cost", type="number", format="float"),
     *                 @OA\Property(property="type", type="string", enum={"simple", "manufactured", "composite"}),
     *                 @OA\Property(property="unit_id", type="integer"),
     *                 @OA\Property(property="is_active", type="boolean"),
     *                 @OA\Property(property="option_values", type="array", @OA\Items(type="integer"))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Product variant created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Product variant successfully created")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid input",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error: Invalid input")
     *         )
     *     )
     * )
     */
    public function store(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|unique:product_variants,sku',
            'price' => 'required|numeric|min:0',
            'additional_cost' => 'numeric|min:0',
            'type' => 'required|string|in:simple,manufactured,composite',
            'unit_id' => 'nullable|exists:units,id',
            'is_active' => 'boolean',
            'option_values' => 'array'
        ]);

        try {
            DB::beginTransaction();

            // Создаем вариант продукта
            $variant = $product->variants()->create([
                'name' => $validated['name'],
                'sku' => $validated['sku'],
                'price' => $validated['price'],
                'additional_cost' => $validated['additional_cost'] ?? 0,
                'type' => $validated['type'],
                'unit_id' => $validated['unit_id'],
                'is_active' => $validated['is_active'] ?? true,
            ]);

            // Если есть значения опций, привязываем их к варианту
            if (!empty($validated['option_values'])) {
                $optionValues = array_filter($validated['option_values']);
                if (!empty($optionValues)) {
                    $variant->optionValues()->attach($optionValues);
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Product variant successfully created',
                'variant' => $variant
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * @OA\Put(
     *     path="/variants/{variant}",
     *     summary="Update a product variant",
     *     description="Update the details of a product variant.",
     *     tags={"Product Variants"},
     *     @OA\Parameter(
     *         name="variant",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 type="object",
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="price", type="number", format="float"),
     *                 @OA\Property(property="stock", type="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Variant updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Variant updated successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid input",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error: Invalid input")
     *         )
     *     )
     * )
     */
    public function update(Request $request, ProductVariant $variant)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
        ]);

        $variant->update($validated);

        return response()->json([
            'message' => 'Variant updated successfully.',
            'variant' => $variant
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/variants/{variant}",
     *     summary="Delete a product variant",
     *     description="Delete a product variant by its ID.",
     *     tags={"Product Variants"},
     *     @OA\Parameter(
     *         name="variant",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Variant deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Variant deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Variant not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error: Variant not found")
     *         )
     *     )
     * )
     */
    public function destroy(ProductVariant $variant)
    {
        $variant->delete();

        return response()->json([
            'message' => 'Variant deleted successfully'
        ]);
    }
    /**
     * @OA\Post(
     *     path="/variants/{variant}/upload-image",
     *     summary="Upload an image for a product variant",
     *     description="Upload an image for the given product variant.",
     *     tags={"Product Variants"},
     *     @OA\Parameter(
     *         name="variant",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 type="object",
     *                 @OA\Property(property="image", type="string", format="binary")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Image uploaded successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Image uploaded successfully"),
     *             @OA\Property(property="image_url", type="string", example="https://example.com/images/variant123.jpg")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid file or file format",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error: Invalid file format")
     *         )
     *     )
     * )
     */
    public function uploadImage(Request $request, ProductVariant $variant)
    {
        $validated = $request->validate([
            'image' => 'required|image|mimes:jpg,jpeg,png,gif|max:2048'
        ]);

        // Загружаем изображение
        $imagePath = $request->file('image')->store('product_variants/' . $variant->id, 'public');

        // Сохраняем ссылку на изображение
        $variant->images()->create([
            'path' => $imagePath,
        ]);

        return response()->json([
            'message' => 'Image uploaded successfully',
            'image_url' => Storage::url($imagePath)
        ]);
    }
    /**
     * @OA\Get(
     *     path="/variants/{variant}/image",
     *     summary="Get image for a product variant",
     *     description="Retrieve the image for the given product variant.",
     *     tags={"Product Variants"},
     *     @OA\Parameter(
     *         name="variant",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Image retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="image_url", type="string", example="https://example.com/images/variant123.jpg")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Image not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error: Image not found")
     *         )
     *     )
     * )
     */
    public function getImage(ProductVariant $variant)
    {
        $image = $variant->images()->first();

        if (!$image) {
            return response()->json([
                'message' => 'Error: Image not found'
            ], 404);
        }

        return response()->json([
            'image_url' => Storage::url($image->path)
        ]);
    }
    /**
     * @OA\Put(
     *     path="/variants/{variant}/update-image",
     *     summary="Update image for a product variant",
     *     description="Update the image for the given product variant.",
     *     tags={"Product Variants"},
     *     @OA\Parameter(
     *         name="variant",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 type="object",
     *                 @OA\Property(property="image", type="string", format="binary")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Image updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Image updated successfully"),
     *             @OA\Property(property="image_url", type="string", example="https://example.com/images/variant123.jpg")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid file or file format",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error: Invalid file format")
     *         )
     *     )
     * )
     */
    public function updateImage(Request $request, ProductVariant $variant)
    {
        $validated = $request->validate([
            'image' => 'required|image|mimes:jpg,jpeg,png,gif|max:2048'
        ]);

        // Удаляем старое изображение, если оно есть
        $variant->images->each->delete();

        // Загружаем новое изображение
        $imagePath = $request->file('image')->store('product_variants/' . $variant->id, 'public');

        // Создаем запись для нового изображения
        $variant->images()->create([
            'path' => $imagePath,
        ]);

        return response()->json([
            'message' => 'Image updated successfully',
            'image_url' => Storage::url($imagePath)
        ]);
    }

}
