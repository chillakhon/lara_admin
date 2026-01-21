<?php

namespace App\Services\OtoBanner;

use App\DTOs\OtoBanner\OtoBannerAnalyticsDTO;
use App\Models\ContactRequest;
use App\Models\Order;
use App\Models\Oto\OtoBanner;
use App\Repositories\OtoBanner\OtoBannerRepository;
use App\Repositories\OtoBanner\OtoBannerSubmissionRepository;
use App\Repositories\OtoBanner\OtoBannerViewRepository;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class OtoBannerAnalyticsService
{
    public function __construct(
        private readonly OtoBannerRepository $bannerRepository,
        private readonly OtoBannerViewRepository $viewRepository,
        private readonly OtoBannerSubmissionRepository $submissionRepository,
    ) {}

    /**
     * Получить аналитику по конкретному баннеру
     */
    public function getBannerAnalytics(OtoBanner $banner, ?Carbon $from = null, ?Carbon $to = null): OtoBannerAnalyticsDTO
    {
        $viewsCount = $this->viewRepository->getViewsCount($banner->id, $from, $to);
        $submissionsCount = $this->submissionRepository->getSubmissionsCount($banner->id, $from, $to);

        // Получаем заказы по заявкам этого баннера
        $orders = $this->getOrdersByBanner($banner->id, $from, $to);

        $ordersCount = $orders->count();
        $totalOrdersAmount = $orders->sum('total_amount');

        return new OtoBannerAnalyticsDTO(
            bannerId: $banner->id,
            bannerName: $banner->name,
            viewsCount: $viewsCount,
            submissionsCount: $submissionsCount,
            conversionRate: $viewsCount > 0 ? round(($submissionsCount / $viewsCount) * 100, 2) : 0.0,
            ordersCount: $ordersCount,
            totalOrdersAmount: (float) $totalOrdersAmount,
        );
    }

    /**
     * Получить сводную аналитику по всем баннерам
     */
    public function getSummaryAnalytics(): array
    {
        $banners = $this->bannerRepository->getBannersWithAnalytics();

        $totalViews = 0;
        $totalSubmissions = 0;
        $totalOrders = 0;
        $totalOrdersAmount = 0.0;

        $bannersAnalytics = $banners->map(function ($banner) use (&$totalViews, &$totalSubmissions, &$totalOrders, &$totalOrdersAmount) {
            $analytics = $this->getBannerAnalytics($banner);

            $totalViews += $analytics->viewsCount;
            $totalSubmissions += $analytics->submissionsCount;
            $totalOrders += $analytics->ordersCount;
            $totalOrdersAmount += $analytics->totalOrdersAmount;

            return $analytics->toArray();
        });

        // Рассчитываем конверсию для каждого баннера относительно всех заявок
        $bannersAnalytics = $bannersAnalytics->map(function ($banner) use ($totalSubmissions) {
            if ($totalSubmissions > 0) {
                $banner['conversion_percentage'] = round(($banner['submissions'] / $totalSubmissions) * 100, 2);
            } else {
                $banner['conversion_percentage'] = 0.0;
            }
            return $banner;
        });

        return [
            'summary' => [
                'total_banners' => $banners->count(),
                'total_views' => $totalViews,
                'total_submissions' => $totalSubmissions,
                'total_orders' => $totalOrders,
                'total_orders_amount' => $totalOrdersAmount,
                'average_conversion_rate' => $totalViews > 0 ? round(($totalSubmissions / $totalViews) * 100, 2) : 0.0,
            ],
            'banners' => $bannersAnalytics,
        ];
    }

    /**
     * Получить график по баннеру
     */
    public function getBannerChart(OtoBanner $banner, string $period = 'day'): array
    {
        $views = $this->viewRepository->getViewsByPeriod($banner->id, $period);
        $submissions = $this->submissionRepository->getSubmissionsByPeriod($banner->id, $period);

        // Объединяем данные по периодам
        $chart = collect($views)->map(function ($view) use ($submissions) {
            $period = $view['period'];
            $submissionData = collect($submissions)->firstWhere('period', $period);

            return [
                'period' => $period,
                'views' => $view['count'],
                'submissions' => $submissionData['count'] ?? 0,
            ];
        });

        return $chart->toArray();
    }

    /**
     * Получить заказы по заявкам баннера
     */
    private function getOrdersByBanner(int $bannerId, ?Carbon $from = null, ?Carbon $to = null): Collection
    {
        // Получаем ID клиентов, оставивших заявки по этому баннеру
        $clientIds = ContactRequest::forBanner($bannerId)
            ->when($from, fn($q) => $q->where('created_at', '>=', $from))
            ->when($to, fn($q) => $q->where('created_at', '<=', $to))
            ->pluck('client_id')
            ->filter()
            ->unique();

        if ($clientIds->isEmpty()) {
            return collect();
        }

        // Получаем заказы этих клиентов
        return Order::whereIn('client_id', $clientIds)
            ->when($from, fn($q) => $q->where('created_at', '>=', $from))
            ->when($to, fn($q) => $q->where('created_at', '<=', $to))
            ->get();
    }

    /**
     * Экспорт аналитики в массив
     */
    public function exportBannerAnalytics(OtoBanner $banner): array
    {
        $analytics = $this->getBannerAnalytics($banner);
        $chart = $this->getBannerChart($banner, 'day');

        return [
            'banner' => [
                'id' => $banner->id,
                'name' => $banner->name,
                'status' => $banner->status->value,
                'device_type' => $banner->device_type->value,
                'created_at' => $banner->created_at->toDateTimeString(),
            ],
            'analytics' => $analytics->toArray(),
            'chart' => $chart,
        ];
    }
}
