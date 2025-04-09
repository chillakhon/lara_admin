<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Image;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\ImageManager;

class ProductImageController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/products/{product}/images",
     *     summary="Получает изображения продукта",
     *     tags={"Product Images"},
     *     @OA\Parameter(
     *         name="product",
     *         in="path",
     *         description="ID продукта",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Список изображений",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="images",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Image")
     *             )
     *         )
     *     )
     * )
     */
    public function index(Product $product)
    {
        $images = $product->images()->get();

        return response()->json([
            'images' => $images,
        ], 200);
    }


    public function getProductImage(Product $product, Request $request)
    {
        $path = $request->get('path');

        if (!$path) {
            return response()->json(['message' => 'Path is required'], 400);
        }

        $filePath = storage_path("app/public/{$path}");


        if (!file_exists($filePath)) {
            $filePath = public_path('images/default.png');
        }

        return response()->file($filePath);
    }



    public function getMainProductImage(Product $product)
    {
        $image = Image::whereHas('products', function ($query) use ($product) {
            $query->where('products.id', $product->id);
        })->where('is_main', true)->first();

        if ($image) {
            $filePath = storage_path("app/public/{$image->path}");
        } else {
            $filePath = public_path('images/default.png');
        }

        if (!file_exists($filePath)) {
            return response()->json(['message' => 'File not found'], 404);
        }

        return response()->file($filePath);
    }






    /**
     * Загружает одно или несколько изображений для продукта и привязывает их к вариантам.
     *
     * @param Request $request
     * @param Product $product
     * @return \Illuminate\Http\JsonResponse
     */
    /**
     * @OA\Post(
     *     path="/api/products/{product}/images",
     *     summary="Загружает изображения для продукта и привязывает их к вариантам",
     *     tags={"Product Images"},
     *     @OA\Parameter(
     *         name="product",
     *         in="path",
     *         description="ID продукта",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"images", "variants"},
     *                 @OA\Property(
     *                     property="images[]",
     *                     type="array",
     *                     @OA\Items(
     *                         type="string",
     *                         format="binary",
     *                         description="Файл изображения"
     *                     ),
     *                     description="Массив изображений"
     *                 ),
     *                 @OA\Property(
     *                     property="variants",
     *                     type="array",
     *                     @OA\Items(
     *                         type="integer"
     *                     ),
     *                     description="Массив ID вариантов продукта, к которым будет привязано изображение"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Изображения успешно загружены",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Images uploaded successfully."),
     *             @OA\Property(
     *                 property="images",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Image")
     *             )
     *         )
     *     )
     * )
     */

    public function store(Request $request, Product $product)
    {
        $validated = $request->validate([
            'images.*' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'variants' => 'array',
            'variants.*' => 'exists:product_variants,id',
        ]);

        $createdImages = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $imageFile) {
                $image = $this->saveProductImage($imageFile, $product);
//                foreach ($validated['variants'] as $variantId) {
//                    $product->images()->attach($image->id, [
//                        'product_variant_id' => $variantId,
//                    ]);
//                }
                $createdImages[] = $image;
                $product->images()->save($image);
            }
        }

        return response()->json([
            'message' => 'Images uploaded successfully.',
            'images' => $createdImages,
        ], 201);
    }

    /**
     * Сохраняет изображение в файловой системе, создаёт миниатюру и записывает информацию в БД.
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @param Product $product
     * @return Image
     */
    private function saveProductImage($file, Product $product)
    {
        $manager = new ImageManager(new ImagickDriver());

        $filename = uniqid() . '.' . $file->getClientOriginalExtension();
        $directory = 'product_images/' . $product->id;
        $path = $directory . '/' . $filename;

        if (!Storage::disk('public')->exists($directory)) {
            Storage::disk('public')->makeDirectory($directory);
        }

        // Сохраняем оригинальное изображение
        $img = $manager->read($file);
        $img->save(storage_path('app/public/' . $path));

        // Создаем и сохраняем миниатюру
        $thumb = $manager->read($file);
        $thumb->cover(300, 300);
        $thumbPath = $directory . '/thumb_' . $filename;
        $thumb->save(storage_path('app/public/' . $thumbPath));

        // Создаем запись в таблице images
        return Image::create([
            'path' => $path,
            'url' => Storage::url($path),
            'order' => $product->images()->count() + 1,
            'is_main' => $product->images()->count() === 0, // Первое изображение делаем основным
        ]);
    }

    /**
     * Удаляет изображение для конкретного варианта продукта.
     *
     * @param Product $product
     * @param Image $image
     * @param int $variantId
     * @return \Illuminate\Http\JsonResponse
     */
    /**
     * @OA\Delete(
     *     path="/api/products/{product}/images/{image}/{variant}",
     *     summary="Удаляет изображение для указанного варианта продукта",
     *     tags={"Product Images"},
     *     @OA\Parameter(
     *         name="product",
     *         in="path",
     *         description="ID продукта",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="image",
     *         in="path",
     *         description="ID изображения",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="variant",
     *         in="path",
     *         description="ID варианта продукта, к которому привязано изображение",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Изображение успешно удалено",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Image deleted successfully.")
     *         )
     *     )
     * )
     */

    public function destroy(Product $product, Image $image, $variantId)
    {
        // Отсоединяем изображение для указанного варианта
        $product->images()->wherePivot('product_variant_id', $variantId)->detach($image->id);

        // Если изображение больше не привязано к данному продукту, удаляем файлы и запись
        if (!$product->images()->where('images.id', $image->id)->exists()) {
            Storage::disk('public')->delete($image->path);
            $thumbPath = $this->getThumbPath($image->path);
            Storage::disk('public')->delete($thumbPath);
            $image->delete();
        }

        return response()->json([
            'message' => 'Image deleted successfully.'
        ], 200);
    }

    public function deleteImg(Product $product, Image $image,){
        // Если изображение больше не привязано к данному продукту, удаляем файлы и запись
        if ($product->images()->where('images.id', $image->id)->exists()) {
            Storage::disk('public')->delete($image->path);
            $thumbPath = $this->getThumbPath($image->path);
            Storage::disk('public')->delete($thumbPath);
            $image->delete();
        }

        return response()->json([
            'message' => 'Image deleted successfully.'
        ], 200);
    }

    /**
     * Устанавливает выбранное изображение в качестве главного для определенного варианта.
     *
     * @param Request $request
     * @param Product $product
     * @param Image $image
     * @return \Illuminate\Http\JsonResponse
     */
    /**
     * @OA\Patch(
     *     path="/api/products/{product}/images/{image}/{variant}/main",
     *     summary="Устанавливает изображение как главное для указанного варианта продукта",
     *     tags={"Product Images"},
     *     @OA\Parameter(
     *         name="product",
     *         in="path",
     *         description="ID продукта",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="image",
     *         in="path",
     *         description="ID изображения",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="variant",
     *         in="path",
     *         description="ID варианта продукта (из URL)",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"variant_id"},
     *             @OA\Property(property="variant_id", type="integer", example=1, description="ID варианта продукта для установки главного изображения")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Главное изображение успешно установлено",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Main image set successfully.")
     *         )
     *     )
     * )
     */

    public function setMain(Request $request, Product $product, Image $image)
    {
        $validated = $request->validate([
            'variant_id' => 'required|exists:product_variants,id',
        ]);

        $variantId = $validated['variant_id'];

        DB::transaction(function () use ($product, $image, $variantId) {
            // Сбрасываем флаг is_main для всех изображений данного продукта и варианта
            Image::whereHas('products', function ($query) use ($product, $variantId) {
                $query->where('products.id', $product->id)
                    ->where('imageables.product_variant_id', $variantId);
            })->update(['is_main' => false]);

            // Устанавливаем выбранное изображение как основное
            $image->update(['is_main' => true]);
        });

        return response()->json([
            'message' => 'Main image set successfully.'
        ], 200);
    }

    /**
     * Возвращает путь к миниатюре для заданного пути оригинального изображения.
     *
     * @param string $path
     * @return string
     */
    private function getThumbPath($path)
    {
        $directory = dirname($path);
        $filename = basename($path);
        return $directory . '/thumb_' . $filename;
    }
}
