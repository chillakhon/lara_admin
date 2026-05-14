<?php

namespace App\Actions\OtoBanner;

use App\DTOs\OtoBanner\UpdateOtoBannerDTO;
use App\Models\Oto\OtoBanner;
use App\Repositories\OtoBanner\OtoBannerRepository;
use App\Services\ImageService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class UpdateOtoBannerAction
{
    public function __construct(
        private readonly OtoBannerRepository $repository,
        private readonly ImageService $imageService,
    ) {}

    public function execute(OtoBanner $banner, UpdateOtoBannerDTO $dto): OtoBanner
    {
        return DB::transaction(function () use ($banner, $dto) {
            // Обновляем данные баннера
            $this->repository->update($banner, $dto->toArray());

            // Обновляем изображение если загружено новое
            if ($dto->image) {
                $this->updateImage($banner, $dto->image);
            }

            return $banner->fresh(['mainImage', 'promoCode']);
        });
    }

    private function updateImage(OtoBanner $banner, $image): void
    {
        // Удаляем старые изображения
        foreach ($banner->images as $oldImage) {
            $this->deleteImageFiles($oldImage->path);
            $oldImage->delete();
        }

        // Загружаем новое
        $paths = $this->imageService->saveImage(
            $image,
            "oto-banners/{$banner->id}",
            800,
            600
        );

        $banner->images()->create([
            'path' => basename($paths['original']),
            'url' => $this->imageService->getImageUrl($paths['original']),
            'is_main' => true,
            'order' => 0,
        ]);
    }

    private function deleteImageFiles(string $filename): void
    {
        foreach (['original', 'lg', 'md', 'sm'] as $size) {
            $path = storage_path("app/public/oto-banners/{$size}_{$filename}");
            if (File::exists($path)) {
                File::delete($path);
            }
        }
    }
}
