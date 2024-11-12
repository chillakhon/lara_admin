<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Image;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;

class ProductImageController extends Controller
{
    public function store(Request $request, Product $product)
    {
        $request->validate([
            'images.*' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'variants' => 'required|array',
            'variants.*' => 'exists:product_variants,id',
        ]);

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $imageFile) {
                $image = $this->saveProductImage($imageFile, $product);

                foreach ($request->variants as $variantId) {
                    $product->images()->attach($image->id, [
                        'product_variant_id' => $variantId,
                    ]);
                }
            }
        }

        return redirect()->back()->with('success', 'Images uploaded successfully.');
    }

    private function saveProductImage($file, Product $product)
    {
        $manager = new ImageManager(new ImagickDriver());

        $filename = uniqid() . '.' . $file->getClientOriginalExtension();
        $directory = 'product_images/' . $product->id;
        $path = $directory . '/' . $filename;

        if (!Storage::disk('public')->exists($directory)) {
            Storage::disk('public')->makeDirectory($directory);
        }

        // Save original image
        $image = $manager->read($file);
        $image->save(storage_path('app/public/' . $path));

        // Create and save thumbnail
        $thumb = $manager->read($file);
        $thumb->cover(300, 300);
        $thumbPath = $directory . '/thumb_' . $filename;
        $thumb->save(storage_path('app/public/' . $thumbPath));

        // Save image information to database
        return Image::create([
            'path' => $path,
            'url' => Storage::url($path),
            'order' => $product->images()->count() + 1,
            'is_main' => $product->images()->count() == 0, // First image is main
        ]);
    }

    public function destroy(Product $product, Image $image, $variantId)
    {
        $product->images()->wherePivot('product_variant_id', $variantId)->detach($image->id);

        if (!$product->images()->where('image_id', $image->id)->exists()) {
            Storage::disk('public')->delete($image->path);
            Storage::disk('public')->delete(str_replace($image->path, 'thumb_' . basename($image->path), $image->path));
            $image->delete();
        }

        return redirect()->back()->with('success', 'Image deleted successfully.');
    }

    public function setMain(Request $request, Product $product, Image $image)
    {
        $variantId = $request->input('variant_id');

        DB::transaction(function () use ($product, $image, $variantId) {
            // Сбрасываем флаг is_main для всех изображений данного варианта
            Image::whereHas('products', function ($query) use ($product, $variantId) {
                $query->where('products.id', $product->id)
                    ->where('imageables.product_variant_id', $variantId);
            })->update(['is_main' => false]);

            // Устанавливаем флаг is_main только для выбранного изображения
            $image->update(['is_main' => true]);

            // Нет необходимости обновлять промежуточную таблицу imagables,
            // так как is_main находится в таблице images
        });

        return back()->with('success', 'Main image set successfully.');
    }
}
