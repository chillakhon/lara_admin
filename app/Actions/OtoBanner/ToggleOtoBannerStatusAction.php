<?php

namespace App\Actions\OtoBanner;

use App\Models\Oto\OtoBanner;
use App\Repositories\OtoBanner\OtoBannerRepository;

class ToggleOtoBannerStatusAction
{
    public function __construct(
        private readonly OtoBannerRepository $repository,
    ) {}

    public function execute(OtoBanner $banner): bool
    {
        return $this->repository->toggleStatus($banner);
    }
}
