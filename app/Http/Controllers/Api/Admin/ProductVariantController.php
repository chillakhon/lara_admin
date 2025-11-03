<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Image;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductVariantController extends Controller
{
    protected ImageService $imageService;

    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }

    /**
     * @OA\Post(
     *     path="/api/products/{product}/variants",
     *     summary="Create a new product variant",
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
     *             required={"name", "sku", "price", "type"},
     *             @OA\Property(property="name", type="string", example="Variant 1"),
     *             @OA\Property(property="sku", type="string", example="variant-1-unique-sku"),
     *             @OA\Property(property="price", type="number", format="float", example=19.99),
     *             @OA\Property(property="additional_cost", type="number", format="float", example=2.50),
     *             @OA\Property(property="type", type="string", example="simple", enum={"simple", "manufactured", "composite"}),
     *             @OA\Property(property="unit_id", type="integer", example=1),
     *             @OA\Property(property="is_active", type="boolean", example=true),
     *             @OA\Property(property="option_values", type="array", @OA\Items(type="integer"), example={1, 2, 3})
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Product variant created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Product variant created successfully"),
     *             @OA\Property(
     *                 property="variant",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Variant 1"),
     *                 @OA\Property(property="sku", type="string", example="variant-1-unique-sku"),
     *                 @OA\Property(property="price", type="number", format="float", example=19.99),
     *                 @OA\Property(property="additional_cost", type="number", format="float", example=2.50),
     *                 @OA\Property(property="type", type="string", example="simple"),
     *                 @OA\Property(property="unit_id", type="integer", example=1),
     *                 @OA\Property(property="is_active", type="boolean", example=true),
     *                 @OA\Property(
     *                     property="option_values",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="Option Value 1")
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
     *                     property="sku",
     *                     type="array",
     *                     @OA\Items(type="string", example="The sku has already been taken.")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Failed to create product variant"),
     *             @OA\Property(property="error", type="string", example="Error message details")
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
            'option_values' => 'array',
        ]);

        try {
            DB::beginTransaction();

            $variant = $product->variants()->create($validated);

            if (!empty($validated['option_values'])) {
                $optionValues = array_filter($validated['option_values']);
                if (!empty($optionValues)) {
                    $variant->optionValues()->attach($optionValues);
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Product variant created successfully',
                'variant' => $variant,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to create product variant',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function update(Request $request, ProductVariant $variant)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
        ]);

        $variant->update($validated);

        return response()->json([
            'message' => 'Variant updated successfully',
            'variant' => $variant,
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/products/{product}/variants/{variant}",
     *     summary="Delete a product variant",
     *     tags={"Product Variants"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="product",
     *         in="path",
     *         description="ID of the product",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="variant",
     *         in="path",
     *         description="ID of the variant",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Product variant deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Product variant deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Failed to delete product variant"),
     *             @OA\Property(property="error", type="string", example="Error message details")
     *         )
     *     )
     * )
     */
    public function destroy(Product $product, ProductVariant $variant)
    {
        try {
            DB::beginTransaction();

            $images = $variant->images;

            $variant->images()->detach();

            foreach ($images as $image) {
                if ($image->products()->doesntExist()) {
                    if (Storage::disk('public')->exists($image->path)) {
                        Storage::disk('public')->delete($image->path);
                    }
                    $image->delete();
                }
            }

            $variant->delete();

            DB::commit();

            return response()->json([
                'message' => 'Product variant deleted successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to delete product variant',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Массовое обновление вариантов продукта.
     */
    public function bulkUpdate(Request $request, Product $product)
    {
        $request->validate([
            'action' => 'required|string|in:images,price,additional_cost,active,sku,name',
            'variants' => 'required|string',
            'value' => 'required_unless:action,images',
            'images.*' => 'required_if:action,images|image|max:40960',
            'name_template' => 'required_if:action,name|string|nullable',
            'sku_template' => 'required_if:action,sku|string|nullable',
        ]);

        try {
            $variantIds = json_decode($request->variants, true);
            if (!is_array($variantIds)) {
                throw new \Exception('Invalid variants data');
            }

            $variants = ProductVariant::whereIn('id', $variantIds)->get();

            if ($variants->isEmpty()) {
                return response()->json([
                    'message' => 'No variants found for update',
                ], 404);
            }

            DB::beginTransaction();

            switch ($request->action) {
                case 'images':
                    if ($request->hasFile('images')) {
                        $uploadedImages = $this->handleBulkImageUpload(
                            $request->file('images'),
                            $variants,
                            $product
                        );
                    }
                    break;

                case 'price':
                    $variants->each(function ($variant) use ($request) {
                        $variant->update(['price' => $request->value]);
                    });
                    break;

                case 'additional_cost':
                    $variants->each(function ($variant) use ($request) {
                        $variant->update(['additional_cost' => $request->value]);
                    });
                    break;

                case 'active':
                    $variants->each(function ($variant) use ($request) {
                        $variant->update(['is_active' => (bool)$request->value]);
                    });
                    break;

                case 'name':
                    $variants->each(function ($variant) use ($request, $product) {
                        $name = $this->processTemplate(
                            $request->name_template,
                            $variant,
                            $product
                        );
                        $variant->update(['name' => $name]);
                    });
                    break;

                case 'sku':
                    $variants->each(function ($variant) use ($request, $product) {
                        $sku = $this->processTemplate(
                            $request->sku_template,
                            $variant,
                            $product
                        );
                        $variant->update(['sku' => $sku]);
                    });
                    break;
            }

            DB::commit();

            return response()->json([
                'message' => 'Variants updated successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to update variants',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Удаление изображения варианта продукта.
     */
    /**
     * @OA\Delete(
     *     path="/api/products/{product}/variants/{variant}/images/{image}",
     *     summary="Удаление изображения варианта продукта",
     *     description="Удаляет изображение, прикрепленное к варианту продукта. Если изображение больше не используется в других продуктах, оно удаляется из базы данных.",
     *     operationId="destroyImage",
     *     tags={"Product Variants"},
     *     @OA\Parameter(
     *         name="product",
     *         in="path",
     *         required=true,
     *         description="ID продукта",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="variant",
     *         in="path",
     *         required=true,
     *         description="ID варианта продукта",
     *         @OA\Schema(type="integer", example=5)
     *     ),
     *     @OA\Parameter(
     *         name="image",
     *         in="path",
     *         required=true,
     *         description="ID изображения",
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Изображение успешно удалено",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(property="message", type="string", example="Image deleted successfully")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Ошибка удаления изображения",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(property="message", type="string", example="Failed to delete image"),
     *                 @OA\Property(property="error", type="string", example="Database error message")
     *             )
     *         )
     *     )
     * )
     */

    public function destroyImage(Product $product, ProductVariant $variant, Image $image)
    {
        try {
            DB::beginTransaction();

            $variant->images()->detach($image->id);

            if ($image->products()->doesntExist()) {
                $this->imageService->deleteImage($image->path);
                $image->delete();
            }

            DB::commit();

            return response()->json([
                'message' => 'Image deleted successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to delete image',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Добавление изображений к варианту продукта.
     */
    /**
     * @OA\Post(
     *     path="/api/products/{product}/variants/{variant}/images",
     *     summary="Add images to a product variant",
     *     tags={"Product Variants"},
     *     @OA\Parameter(
     *         name="product",
     *         in="path",
     *         required=true,
     *         description="ID of the product",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="variant",
     *         in="path",
     *         required=true,
     *         description="ID of the product variant",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"images"},
     *                 @OA\Property(
     *                     property="images",
     *                     type="array",
     *                     @OA\Items(type="string", format="binary")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Images added successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Images added successfully"),
     *             @OA\Property(
     *                 property="images",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Image")
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
     *                     property="images",
     *                     type="array",
     *                     @OA\Items(type="string", example="The images field is required.")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Failed to add images"),
     *             @OA\Property(property="error", type="string", example="Error message details")
     *         )
     *     )
     * )
     */
    public function addImages(Request $request, Product $product, ProductVariant $variant)
    {
        $request->validate([
            'images' => 'required|array',
            'images.*' => 'required|image|max:40960',
        ]);

        try {
            DB::beginTransaction();

            $uploadedImages = [];

            foreach ($request->file('images') as $image) {
                $paths = $this->imageService->saveImage(
                    $image,
                    "products/{$product->id}/variants",
                    300,
                    300
                );

                $imageModel = Image::create([
                    'path' => $paths['original'],
                    'url' => $this->imageService->getImageUrl($paths['original']),
                    'order' => $variant->images()->count() + count($uploadedImages) + 1,
                    'is_main' => !$variant->images()->exists() && count($uploadedImages) === 0,
                ]);

                $variant->images()->attach($imageModel->id);

                $uploadedImages[] = $imageModel;
            }

            DB::commit();

            return response()->json([
                'message' => 'Images added successfully',
                'images' => $uploadedImages,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to add images',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Обработка загрузки изображений для массового обновления.
     */
    private function handleBulkImageUpload($images, $variants, $product)
    {
        $uploadedImages = [];

        foreach ($images as $image) {
            try {
                DB::beginTransaction();

                $paths = $this->imageService->saveImage(
                    $image,
                    "products/{$product->id}/variants",
                    300,
                    300
                );

                $imageModel = Image::create([
                    'path' => $paths['original'],
                    'url' => $this->imageService->getImageUrl($paths['original']),
                    'order' => count($uploadedImages) + 1,
                    'is_main' => count($uploadedImages) === 0,
                ]);

                foreach ($variants as $variant) {
                    $variant->images()->attach($imageModel->id);
                }

                $uploadedImages[] = $imageModel;

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                if (isset($paths)) {
                    $this->imageService->deleteImage($paths['original']);
                }
                throw $e;
            }
        }

        return $uploadedImages;
    }

    /**
     * Обработка шаблонов для имени и SKU.
     */
    private function processTemplate($template, $variant, $product)
    {
        $result = $template;

        $result = str_replace('{product_name}', $product->name, $result);
        $result = str_replace('{variant_id}', $variant->id, $result);

        foreach ($variant->optionValues as $optionValue) {
            $placeholder = '{' . Str::slug($optionValue->option->name) . '}';
            $result = str_replace($placeholder, $optionValue->name, $result);
        }

        return $result;
    }
}
