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
