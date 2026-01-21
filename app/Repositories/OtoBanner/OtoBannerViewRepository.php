<?php

namespace App\Repositories\OtoBanner;

use App\Models\Oto\OtoBannerView;
use Carbon\Carbon;

class OtoBannerViewRepository
{
    /**
     * Создать запись о просмотре
     */
    public function create(array $data): OtoBannerView
    {
        return OtoBannerView::create(array_merge($data, [
            'viewed_at' => Carbon::now(),
        ]));
    }

    /**
     * Проверить, был ли просмотр в текущей сессии
     */
    public function hasViewedInSession(int $bannerId, string $sessionId): bool
    {
        return OtoBannerView::where('oto_banner_id', $bannerId)
            ->where('session_id', $sessionId)
            ->exists();
    }

    /**
     * Получить количество просмотров баннера
     */
    public function getViewsCount(int $bannerId, ?Carbon $from = null, ?Carbon $to = null): int
    {
        $query = OtoBannerView::where('oto_banner_id', $bannerId);

        if ($from) {
            $query->where('viewed_at', '>=', $from);
        }

        if ($to) {
            $query->where('viewed_at', '<=', $to);
        }

        return $query->count();
    }

    /**
     * Получить уникальные просмотры по IP
     */
    public function getUniqueViewsCount(int $bannerId, ?Carbon $from = null, ?Carbon $to = null): int
    {
        $query = OtoBannerView::where('oto_banner_id', $bannerId);

        if ($from) {
            $query->where('viewed_at', '>=', $from);
        }

        if ($to) {
            $query->where('viewed_at', '<=', $to);
        }

        return $query->distinct('ip_address')->count('ip_address');
    }

    /**
     * Получить просмотры по периодам
     */
    public function getViewsByPeriod(int $bannerId, string $period = 'day'): array
    {
        $groupBy = match($period) {
            'hour' => "DATE_FORMAT(viewed_at, '%Y-%m-%d %H:00:00')",
            'day' => "DATE(viewed_at)",
            'month' => "DATE_FORMAT(viewed_at, '%Y-%m-01')",
            default => "DATE(viewed_at)",
        };

        return OtoBannerView::where('oto_banner_id', $bannerId)
            ->selectRaw("$groupBy as period, COUNT(*) as count")
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->toArray();
    }
}
