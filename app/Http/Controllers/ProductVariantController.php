<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductSize;
use App\Models\ColorOptionValue;
use App\Models\Image;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;

class ProductVariantController extends Controller
{
    public function store(Request $request, Product $product)
    {
        $request->validate([
            'variants' => 'required|array',
            'variants.*.size_id' => 'required|exists:product_sizes,id',
            'variants.*.price' => 'required|numeric|min:0',
            'variants.*.stock' => 'required|integer|min:0',
            'variants.*.color_combination' => 'required|array',
            'images' => 'required|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:40960',
        ]);

        $createdVariants = [];

        foreach ($request->variants as $variantData) {
            $size = ProductSize::findOrFail($variantData['size_id']);
            $colorCombination = $variantData['color_combination'];

            $variantName = $this->generateVariantName($product, $size, $colorCombination);

            $variant = $product->variants()->create([
                'name' => $variantName,
                'article' => Str::random(8),
                'price' => $variantData['price'],
                'stock' => $variantData['stock'],
                'product_size_id' => $size->id,
            ]);

            // Attach color option values to the variant
            foreach ($colorCombination as $colorOptionId => $colorValueId) {
                $variant->colorOptionValues()->attach($colorValueId, ['color_option_id' => $colorOptionId]);
            }

            $createdVariants[] = $variant;
        }

        if ($request->hasFile('images')) {
            $this->handleImageUpload($request->file('images'), $createdVariants, $product);
        }

        return back()->with('success', 'Product variants created successfully.');
    }

    private function generateVariantName($product, $size, $colorCombination)
    {
        $name = $product->name . " - " . $size->name;
        foreach ($colorCombination as $colorOptionId => $colorValueId) {
            $colorValue = ColorOptionValue::find($colorValueId);
            $name .= " - " . $colorValue->color->title;
        }
        return $name;
    }

    private function handleImageUpload($images, $variants, $product)
    {
        $manager = new ImageManager(new ImagickDriver());

        foreach ($images as $image) {
            $filename = uniqid() . '.' . $image->getClientOriginalExtension();
            $directory = 'product_images/' . $product->id;
            $path = $directory . '/' . $filename;

            if (!Storage::disk('public')->exists($directory)) {
                Storage::disk('public')->makeDirectory($directory);
            }

            // Save original image
            $img = $manager->read($image);
            $img->save(storage_path('app/public/' . $path));

            // Create and save thumbnail
            $thumb = $manager->read($image);
            $thumb->cover(300, 300);
            $thumbPath = $directory . '/thumb_' . $filename;
            $thumb->save(storage_path('app/public/' . $thumbPath));

            // Save image information to database
            $imageModel = Image::create([
                'path' => $path,
                'url' => Storage::url($path),
                'order' => 1,
                'is_main' => false,
            ]);

            // Associate image with all variants
            foreach ($variants as $variant) {
                $product->images()->attach($imageModel->id, [
                    'product_variant_id' => $variant->id,
                ]);
            }
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
}
