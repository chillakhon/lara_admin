<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Image;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;

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

            return back()->with('success', 'Вариант продукта успешно создан');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Ошибка при создании варианта продукта: ' . $e->getMessage());
        }
    }

    public function update(Request $request, ProductVariant $variant)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
        ]);

        $variant->update($validated);

        return back()->with('success', 'Variant updated successfully.');
    }

    public function destroy(Product $product, ProductVariant $variant)
    {
        // Получаем все изображения, связанные с этим вариантом
        $images = $variant->images;

        // Удаляем связи между вариантом и изображениями
        $variant->images()->detach();

        // Проверяем каждое изображение
        foreach ($images as $image) {
            // Если изображение больше не связано ни с какими вариантами, удаляем его
            if ($image->products()->doesntExist()) {
                // Удаляем файл изображения
                if (Storage::disk('public')->exists($image->path)) {
                    Storage::disk('public')->delete($image->path);
                }
                // Удаляем запись из базы данных
                $image->delete();
            }
        }

        // Удаляем сам вариант
        $variant->delete();

        return back()->with('success', 'Product variant deleted successfully.');
    }

    public function bulkUpdate(Request $request, Product $product)
    {

        Log::info('Bulk update request received', [
            'action' => $request->action,
            'has_files' => $request->hasFile('images'),
            'files_count' => $request->hasFile('images') ? count($request->file('images')) : 0,
            'variants' => $request->variants
        ]);

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
                return back()->with('error', 'Не найдено вариантов для обновления');
            }

            DB::beginTransaction();

            switch ($request->action) {
                case 'images':

                    if ($request->hasFile('images')) {
                        Log::info('Processing images', [
                            'count' => count($request->file('images'))
                        ]);
                        $uploadedImages = $this->handleBulkImageUpload(
                            $request->file('images'),
                            $variants,
                            $product
                        );
                        Log::info('Images processed', [
                            'uploaded_count' => count($uploadedImages)
                        ]);
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
            return back()->with('success', 'Варианты успешно обновлены');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Ошибка при обновлении вариантов: ' . $e->getMessage());
        }
    }

    private function handleBulkImageUpload($images, $variants, $product)
    {
        $uploadedImages = [];

        foreach ($images as $image) {
            try {
                DB::beginTransaction();

                // Сохраняем изображение через сервис
                $paths = $this->imageService->saveImage(
                    $image,
                    "products/{$product->id}/variants",  // Путь без 'public/'
                    300,
                    300
                );

                // Создаем запись изображения
                $imageModel = Image::create([
                    'path' => $paths['original'],
                    'url' => $this->imageService->getImageUrl($paths['original']),
                    'order' => count($uploadedImages) + 1,
                    'is_main' => count($uploadedImages) === 0,
                ]);

                Log::info('Image model created', [
                    'image_id' => $imageModel->id,
                    'path' => $imageModel->path,
                    'url' => $imageModel->url
                ]);

                // Связываем изображение с продуктом и его вариантами
                foreach ($variants as $variant) {
                    $variant->images()->attach($imageModel->id);;
                }

                $uploadedImages[] = $imageModel;
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Error in image upload', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                if (isset($paths)) {
                    $this->imageService->deleteImage($paths['original']);
                }
                throw $e;
            }
        }

        return $uploadedImages;
    }

    private function processTemplate($template, $variant, $product)
    {
        $result = $template;

        // Заменяем базовые плейсхолдеры
        $result = str_replace('{product_name}', $product->name, $result);
        $result = str_replace('{variant_id}', $variant->id, $result);

        // Заменяем плейсхолдеры опций
        foreach ($variant->optionValues as $optionValue) {
            $placeholder = '{' . Str::slug($optionValue->option->name) . '}';
            $result = str_replace($placeholder, $optionValue->name, $result);
        }

        return $result;
    }

    public function destroyImage(Product $product, ProductVariant $variant, Image $image)
    {
        try {
            DB::beginTransaction();

            // Отвязываем изображение от варианта
            $variant->images()->detach($image->id);

            // Если изображение больше не используется другими вариантами
            if ($image->products()->doesntExist()) {
                // Удаляем физический файл
                $this->imageService->deleteImage($image->path);
                // Удаляем запись из БД
                $image->delete();
            }

            DB::commit();
            return back()->with('success', 'Изображение успешно удалено');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Ошибка при удалении изображения');
        }
    }

    public function addImages(Request $request, Product $product, ProductVariant $variant)
    {
        $request->validate([
            'images' => 'required|array',
            'images.*' => 'required|image|max:40960'
        ]);

        try {
            DB::beginTransaction();

            $uploadedImages = [];

            foreach ($request->file('images') as $image) {
                // Сохраняем изображение
                $paths = $this->imageService->saveImage(
                    $image,
                    "products/{$product->id}/variants",
                    300,
                    300
                );

                // Создаем запись изображения
                $imageModel = Image::create([
                    'path' => $paths['original'],
                    'url' => $this->imageService->getImageUrl($paths['original']),
                    'order' => $variant->images()->count() + count($uploadedImages) + 1,
                    'is_main' => !$variant->images()->exists() && count($uploadedImages) === 0,
                ]);

                // Привязываем к варианту
                $variant->images()->attach($imageModel->id);

                $uploadedImages[] = $imageModel;
            }

            DB::commit();
            return back()->with('success', 'Изображения успешно добавлены');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Ошибка при загрузке изображений');
        }
    }
}
