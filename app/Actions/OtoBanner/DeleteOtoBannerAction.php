<?php

namespace App\Actions\OtoBanner;

use App\Models\Oto\OtoBanner;
use App\Repositories\OtoBanner\OtoBannerRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class DeleteOtoBannerAction
{
    public function __construct(
        private readonly OtoBannerRepository $repository,
    ) {}

    public function execute(OtoBanner $banner): bool
    {
        return DB::transaction(function () use ($banner) {
            // Удаляем изображения с диска
            foreach ($banner->images as $image) {
                $this->deleteImageFiles($image->path);
                $image->delete();
            }

            // Мягкое удаление баннера
            return $this->repository->delete($banner);
        });
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
