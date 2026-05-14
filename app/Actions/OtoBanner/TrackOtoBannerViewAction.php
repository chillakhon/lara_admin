<?php

namespace App\Actions\OtoBanner;

use App\Models\Oto\OtoBanner;
use App\Repositories\OtoBanner\OtoBannerViewRepository;

class TrackOtoBannerViewAction
{
    public function __construct(
        private readonly OtoBannerViewRepository $repository,
    ) {}

    public function execute(
        OtoBanner $banner,
        ?int $clientId,
        string $ipAddress,
        string $userAgent,
        string $sessionId
    ): bool {
        // Проверяем, был ли уже просмотр в этой сессии
        if ($this->repository->hasViewedInSession($banner->id, $sessionId)) {
            return false;
        }

        $this->repository->create([
            'oto_banner_id' => $banner->id,
            'client_id' => $clientId,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'session_id' => $sessionId,
        ]);

        return true;
    }
}
