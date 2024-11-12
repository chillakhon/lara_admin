<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Imagick\Driver;
use Illuminate\Support\Str;

class ImageService
{
    private const THUMB_POSTFIX = '_thumb';
    private ImageManager $manager;

    public function __construct()
    {
        $this->manager = new ImageManager(new Driver());
    }

    public function saveImage(UploadedFile $image, string $path, int $thumbWidth = 200, int $thumbHeight = 200): array
    {
        try {
            // Нормализуем путь
            $path = trim($path, '/');

            Log::info('Saving image', [
                'original_path' => $path,
                'normalized_path' => $path
            ]);

            // Создаем директорию
            if (!Storage::disk('public')->exists($path)) {
                Storage::disk('public')->makeDirectory($path);
            }

            // Генерируем уникальное имя файла
            $fileName = Str::uuid() . '.' . $image->getClientOriginalExtension();
            $filePath = $path . '/' . $fileName;

            // Получаем изображение
            $img = $this->manager->read($image);

            // Сохраняем оригинал
            Storage::disk('public')->put($filePath, $img->encode()->toString());

            // Создаем миниатюру
            $thumbName = pathinfo($fileName, PATHINFO_FILENAME)
                . self::THUMB_POSTFIX
                . '_' . $thumbWidth . 'x' . $thumbHeight
                . '.' . $image->getClientOriginalExtension();

            $thumbPath = $path . '/' . $thumbName;

            $thumb = $this->manager->read($image);
            $thumb->cover($thumbWidth, $thumbHeight);

            Storage::disk('public')->put($thumbPath, $thumb->encode()->toString());

            Log::info('Image saved successfully', [
                'original_path' => $filePath,
                'thumb_path' => $thumbPath
            ]);

            return [
                'original' => $filePath,
                'thumbnail' => $thumbPath
            ];
        } catch (\Exception $e) {
            Log::error('Error saving image', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    public function deleteImage(string $path): bool
    {
        try {
            // Удаляем оригинал
            $deleted = Storage::delete($path);

            // Формируем имя миниатюры для поиска
            $pathInfo = pathinfo($path);
            $directory = $pathInfo['dirname'];
            $filename = $pathInfo['filename'];
            $extension = $pathInfo['extension'];

            // Получаем список всех файлов в директории
            $files = Storage::disk('public')->files($directory);

            $thumbPattern = $filename . self::THUMB_POSTFIX . '_';
            foreach ($files as $file) {
                $fileInfo = pathinfo($file);
                if (str_starts_with($fileInfo['filename'], $thumbPattern)
                    && $fileInfo['extension'] === $extension) {
                    Storage::disk('public')->delete($file);
                }
            }

            return $deleted;
        } catch (\Exception $e) {
            logger()->error('Error deleting image:', [
                'path' => $path,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    public function getImageUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }
        return Storage::disk('public')->url($path);
    }

    public function getThumbnailUrl(?string $path, int $width = 200, int $height = 200): ?string
    {
        if (!$path) {
            return null;
        }

        $pathInfo = pathinfo($path);
        $thumbPath = $pathInfo['dirname'] . '/' .
            $pathInfo['filename'] .
            self::THUMB_POSTFIX .
            '_' . $width . 'x' . $height .
            '.' . $pathInfo['extension'];

        return Storage::disk('public')->url($thumbPath);
    }

    public function cleanDirectory(string $directory): bool
    {
        try {
            return Storage::disk('public')->deleteDirectory($directory);
        } catch (\Exception $e) {
            logger()->error('Error cleaning directory:', [
                'directory' => $directory,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    public function exists(string $path): bool
    {
        if (!Storage::exists($path)) {
            return false;
        }

        $pathInfo = pathinfo($path);
        $directory = $pathInfo['dirname'];
        $filename = $pathInfo['filename'];
        $extension = $pathInfo['extension'];

        $files = Storage::files($directory);
        $thumbPattern = $filename . self::THUMB_POSTFIX . '_';

        foreach ($files as $file) {
            $fileInfo = pathinfo($file);
            if (str_starts_with($fileInfo['filename'], $thumbPattern)
                && $fileInfo['extension'] === $extension) {
                return true;
            }
        }

        return false;
    }
}
