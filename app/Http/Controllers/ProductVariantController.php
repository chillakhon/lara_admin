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
            'color_option_value_id' => 'required|exists:color_option_values,id',
            'variants' => 'required|array',
            'variants.*.size_id' => 'required|exists:product_sizes,id',
            'variants.*.price' => 'required|numeric|min:0',
            'variants.*.stock' => 'required|integer|min:0',
            'images' => 'required|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:40960',
        ]);

        $colorValue = ColorOptionValue::findOrFail($request->color_option_value_id);
        $createdVariants = [];

        foreach ($request->variants as $variantData) {
            $size = ProductSize::findOrFail($variantData['size_id']);

            $variant = $product->variants()->create([
                'name' => "{$product->name} - {$size->name} - {$colorValue->color->title}",
                'article' => Str::random(8),
                'price' => $variantData['price'],
                'stock' => $variantData['stock'],
                'product_size_id' => $size->id,
                'color_option_value_id' => $colorValue->id,
            ]);

            $createdVariants[] = $variant;
        }

        if ($request->hasFile('images')) {
            $this->handleImageUpload($request->file('images'), $createdVariants, $product);
        }

        return back()->with('success', 'Product variants created successfully.');
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


    public function destroy(Product $product, ProductVariant $variant)
    {
        // Delete associated images
        $images = $variant->images;
        if ($images && is_iterable($images)) {
            foreach ($images as $image) {
                Storage::disk('public')->delete($image->path);
                Storage::disk('public')->delete(str_replace($image->path, 'thumb_' . basename($image->path), $image->path));
                $image->delete();
            }
        }

        $variant->delete();
        return back()->with('success', 'Product variant deleted successfully.');
    }
}
