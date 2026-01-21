<?php

namespace App\Actions\OtoBanner;

use App\Models\Oto\OtoBanner;
use App\Repositories\OtoBanner\OtoBannerRepository;
use Illuminate\Support\Facades\DB;

class DuplicateOtoBannerAction
{
    public function __construct(
        private readonly OtoBannerRepository $repository,
    ) {}

    public function execute(OtoBanner $banner): OtoBanner
    {
        return DB::transaction(function () use ($banner) {
            return $this->repository->duplicate($banner);
        });
    }
}
