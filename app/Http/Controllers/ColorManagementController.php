<?php

namespace App\Http\Controllers;

use App\Models\Color;
use App\Models\ColorCategory;
use App\Models\Image;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;


class ColorManagementController extends Controller
{
    public function index()
    {
        $categories = ColorCategory::with(['colors.images'])->get();
        return Inertia::render('Dashboard/ColorManagement/Index', ['categories' => $categories]);
    }

    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
        ]);

        ColorCategory::create($validated);

        return redirect()->back()->with('success', 'Color category created successfully.');
    }

    public function updateCategory(Request $request, ColorCategory $category)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $category->update($validated);

        return redirect()->back()->with('success', 'Color category updated successfully.');
    }

    public function destroyCategory(ColorCategory $category)
    {
        $category->delete();

        return redirect()->back()->with('success', 'Color category deleted successfully.');
    }

    public function storeColor(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'code' => 'required|string|max:7',
            'color_category_id' => 'required|exists:color_categories,id',
            'image' => 'nullable|image|max:2048',
        ]);

        $color = Color::create(
            [
                'title' => $validated['title'],
                'code' => $validated['code'],
                'color_category_id' => $validated['color_category_id']
            ]);

        if ($request->hasFile('image')) {
            $this->saveColorImage($request->file('image'), $color);
        }

        return redirect()->back()->with('success', 'Color created successfully.');
    }

    public function updateColor(Request $request, Color $color)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'code' => 'required|string|max:7',
            'color_category_id' => 'required|exists:color_categories,id',
        ]);

        $color->update($validated);

        if ($request->hasFile('image')) {
            // Удаляем старые изображения
            $this->deleteColorImages($color);
            $this->saveColorImage($request->file('image'), $color);
        }

        return redirect()->back()->with('success', 'Color updated successfully.');
    }

    public function destroyColor(Color $color)
    {
        $this->deleteColorImages($color);
        $color->delete();

        return redirect()->back()->with('success', 'Color deleted successfully.');
    }

    private function saveColorImage($file, Color $color)
    {
        $manager = new ImageManager(new ImagickDriver());

        $filename = Str::random(40) . '.' . $file->getClientOriginalExtension();
        $directory = 'color_images';
        $path = $directory . '/' . $filename;

        if (!Storage::disk('public')->exists($directory)) {
            Storage::disk('public')->makeDirectory($directory);
        }

        // Сохраняем оригинальное изображение
        $image = $manager->read($file);
        $image->save(storage_path('app/public/' . $path));

        // Создаем и сохраняем уменьшенную версию
        $thumb = $manager->read($file);
        $thumb->cover(32, 32);
        $thumbPath = $directory . '/thumb30x30_' . $filename;
        $thumb->save(storage_path('app/public/' . $thumbPath));

        // Сохраняем информацию об изображениях в базе данных
        $originalImage = Image::create([
            'path' => $path,
            'url' => Storage::url($path),
            'order' => 1,
            'is_main' => true,
        ]);

        $thumbnailImage = Image::create([
            'path' => $thumbPath,
            'url' => Storage::url($thumbPath),
            'order' => 2,
            'is_main' => false,
        ]);

        // Создаем записи в таблице imageables
        DB::table('imagables')->insert([
            [
                'image_id' => $originalImage->id,
                'imagable_id' => $color->id,
                'imagable_type' => Color::class,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'image_id' => $thumbnailImage->id,
                'imagable_id' => $color->id,
                'imagable_type' => Color::class,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }

    private function deleteColorImages(Color $color)
    {
        foreach ($color->images as $image) {
            Storage::disk('public')->delete($image->path);
            DB::table('imagables')->where('image_id', $image->id)->delete();
            $image->delete();
        }
    }
}
