<?php

namespace App\Repositories\OtoBanner;

use App\Enums\Oto\OtoBannerDeviceType;
use App\Enums\Oto\OtoBannerStatus;
use App\Models\Oto\OtoBanner;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class OtoBannerRepository
{
    /**
     * Получить список баннеров с пагинацией
     */
    public function paginate(int $perPage = 20, array $filters = []): LengthAwarePaginator
    {
        $query = OtoBanner::query()
            ->with(['mainImage','promoCode'])
            ->withCount(['views', 'submissions']);

        // Фильтр по статусу
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Фильтр по типу устройства
        if (isset($filters['device_type'])) {
            $query->where('device_type', $filters['device_type']);
        }

        // Поиск по названию
        if (isset($filters['search'])) {
            $query->where('name', 'like', '%' . $filters['search'] . '%');
        }

        // Фильтр по дате создания
        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Найти баннер по ID с отношениями
     */
    public function findWithRelations(int $id): ?OtoBanner
    {
        return OtoBanner::with(['images', 'mainImage'])
            ->withCount(['views', 'submissions'])
            ->find($id);
    }

    /**
     * Создать баннер
     */
    public function create(array $data): OtoBanner
    {
        return OtoBanner::create($data);
    }

    /**
     * Обновить баннер
     */
    public function update(OtoBanner $banner, array $data): bool
    {
        return $banner->update($data);
    }

    /**
     * Удалить баннер (soft delete)
     */
    public function delete(OtoBanner $banner): bool
    {
        return $banner->delete();
    }

    /**
     * Дублировать баннер
     */
    public function duplicate(OtoBanner $banner): OtoBanner
    {
        $newBanner = $banner->replicate();
        $newBanner->name = $banner->name . ' (копия)';
        $newBanner->status = OtoBannerStatus::INACTIVE;
        $newBanner->save();

        // Копируем изображения
        foreach ($banner->images as $image) {
            $newImage = $image->replicate();
            $newImage->item_id = $newBanner->id;
            $newImage->save();
        }

        return $newBanner;
    }

    /**
     * Получить активный баннер для устройства
     */
    public function getActiveBannerForDevice(OtoBannerDeviceType $deviceType): ?OtoBanner
    {
        return OtoBanner::active()
            ->forDevice($deviceType)
            ->with(['mainImage'])
            ->first();
    }

    /**
     * Получить все активные баннеры
     */
    public function getActiveBanners(): Collection
    {
        return OtoBanner::active()
            ->with(['mainImage'])
            ->get();
    }

    /**
     * Переключить статус баннера
     */
    public function toggleStatus(OtoBanner $banner): bool
    {
        $newStatus = $banner->status === OtoBannerStatus::ACTIVE
            ? OtoBannerStatus::INACTIVE
            : OtoBannerStatus::ACTIVE;

        return $banner->update(['status' => $newStatus]);
    }

    /**
     * Получить баннеры с аналитикой
     */
    public function getBannersWithAnalytics(): Collection
    {
        return OtoBanner::withCount(['views', 'submissions'])
            ->get()
            ->map(function ($banner) {
                $banner->conversion_rate = $banner->conversion_rate;
                return $banner;
            });
    }
}
