<?php

namespace App\Actions\OtoBanner;

use App\DTOs\OtoBanner\CreateOtoBannerDTO;
use App\Models\Oto\OtoBanner;
use App\Repositories\OtoBanner\OtoBannerRepository;
use App\Services\ImageService;
use Illuminate\Support\Facades\DB;

class CreateOtoBannerAction
{
    public function __construct(
        private readonly OtoBannerRepository $repository,
        private readonly ImageService $imageService,
    ) {}

    public function execute(CreateOtoBannerDTO $dto): OtoBanner
    {
        return DB::transaction(function () use ($dto) {
            // Создаём баннер
            $banner = $this->repository->create($dto->toArray());

            // Загружаем изображение если есть
            if ($dto->image) {
                $this->uploadImage($banner, $dto->image);
            }

            return $banner->load(['mainImage']);
        });
    }

    private function uploadImage(OtoBanner $banner, $image): void
    {
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
}
