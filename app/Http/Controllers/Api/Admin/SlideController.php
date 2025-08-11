<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SlideRequest;
use App\Http\Resources\SlideResource;
use App\Models\Slide;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Illuminate\Support\Facades\File;

class SlideController extends Controller
{


    public function index()
    {
        $slides = Slide::orderBy('order')->get();
        return SlideResource::collection($slides);
    }


    public function getSlidesForFrontend()
    {
        $slides = Slide::orderBy('order')
            ->where('is_active', 1)
            ->get();
        return SlideResource::collection($slides);
    }

    public function show(Slide $slide)
    {
        return new SlideResource($slide);
    }

    // Создаем или обновляем слайд
    public function store(SlideRequest $request)
    {
//        if (Slide::count() >= 3) {
//            return response()->json(['message' => 'Нельзя создать более 3 слайдов.'], Response::HTTP_UNPROCESSABLE_ENTITY);
//        }

        $data = $request->validated();


        $checkOrder = Slide::where('order', $data['order'] ?? null)
            ->where('is_active', 1)
            ->exists();

        if ($checkOrder) {
            if ($checkOrder) {
                return response()->json([
                    'message' => 'Слайд с таким порядком уже существует и активен.'
                ], 422);
            }
        }


        if ($request->hasFile('image')) {
            $paths = $this->processAndSaveImage($request->file('image'), $data);
            $data['image_paths'] = json_encode($paths);
        }

        $slide = Slide::create($data);

        return (new SlideResource($slide))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(SlideRequest $request, Slide $slide)
    {
        $data = $request->validated();


        $checkOrder = Slide::where('order', $data['order'] ?? null)
            ->where('is_active', 1)
            ->where('id', '!=', $slide->id)
            ->exists();

        if ($checkOrder) {
            if ($checkOrder) {
                return response()->json([
                    'message' => 'Слайд с таким порядком уже существует и активен.'
                ], 422);
            }
        }

        if ($request->hasFile('image')) {
            // Удаляем старые изображения всех размеров
            $this->deleteImageVariants($slide);

            // Сохраняем новые версии
            $paths = $this->processAndSaveImage($request->file('image'), $data);
            $data['image_paths'] = json_encode($paths);
        }

        $slide->update($data);

        return new SlideResource($slide->fresh());
    }

    public function destroy(Slide $slide)
    {
        // Удаляем все версии изображения
        $this->deleteImageVariants($slide);

        $slide->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }


    public function getSlideImage(Request $request)
    {
        $path = $request->get('path');

        if (!$path) {
            return response()->json(['message' => 'Path is required'], 400);
        }

        // Безопасно очистим путь, чтобы избежать directory traversal
        $cleanPath = basename($path);

        $filePath = storage_path("app/public/slides/{$cleanPath}");

        if (!file_exists($filePath)) {
            $filePath = public_path('images/default.png');
        }

        return response()->file($filePath);
    }


    /**
     * Обработка и сохранение изображения в нескольких размерах.
     * Возвращает массив путей для сохранения в базе.
     */
    protected function processAndSaveImage($file, $data = [])
    {
        $img_names = ['original', 'lg', 'md', 'sm'];
        $img_sizes = [null, 1920, 1280, 640];
        $ext = $file->getClientOriginalExtension();
        $uuid = (string)Str::uuid();

        $full_path = storage_path('app/public/slides/');
        if (!File::exists($full_path)) {
            File::makeDirectory($full_path, 0755, true);
        }

        $manager = new ImageManager(new ImagickDriver());
        $originalImage = $manager->read($file->getRealPath());

        $paths = [];

        foreach ($img_names as $i => $prefix) {
            $image_name = "{$prefix}_{$uuid}.{$ext}";
            $image_path = $full_path . $image_name;

            // Клонируем оригинал, чтобы не менять исходный объект
            $img = clone $originalImage;

            if ($img_sizes[$i] !== null) {
                $img->scale(width: $img_sizes[$i]);
            }

            $img->save($image_path);

            $paths[$prefix] = 'slides/' . $image_name;
        }


        return $paths;
    }

    /**
     * Удаляет все версии изображений, связанные со слайдом
     */
    protected function deleteImageVariants(Slide $slide)
    {
        if (!$slide->image_paths) {
            return;
        }

        $paths = json_decode($slide->image_paths, true);
        if (!is_array($paths)) {
            return;
        }

        foreach ($paths as $path) {
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }
    }
}
