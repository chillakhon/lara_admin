<?php

namespace App\DTOs\OtoBanner;

class OtoBannerAnalyticsDTO
{
    public function __construct(
        public readonly int $bannerId,
        public readonly string $bannerName,
        public readonly int $viewsCount,
        public readonly int $submissionsCount,
        public readonly float $conversionRate,
        public readonly int $ordersCount,
        public readonly float $totalOrdersAmount,
    ) {}

    public static function fromBanner($banner, int $ordersCount = 0, float $totalOrdersAmount = 0.0): self
    {
        return new self(
            bannerId: $banner->id,
            bannerName: $banner->name,
            viewsCount: $banner->views()->count(),
            submissionsCount: $banner->submissions()->count(),
            conversionRate: $banner->conversion_rate,
            ordersCount: $ordersCount,
            totalOrdersAmount: $totalOrdersAmount,
        );
    }

    public function toArray(): array
    {
        return [
            'banner_id' => $this->bannerId,
            'banner_name' => $this->bannerName,
            'views' => $this->viewsCount,
            'submissions' => $this->submissionsCount,
            'conversion_rate' => $this->conversionRate,
            'orders_count' => $this->ordersCount,
            'total_orders_amount' => $this->totalOrdersAmount,
        ];
    }
}
