<?php

namespace App\Repositories\OtoBanner;

use App\Models\ContactRequest;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class OtoBannerSubmissionRepository
{
    /**
     * Создать заявку
     */
    public function create(array $data): ContactRequest
    {
        return ContactRequest::create($data);
    }

    /**
     * Получить заявки по баннеру с пагинацией
     */
    public function getByBanner(int $bannerId, int $perPage = 20): LengthAwarePaginator
    {
        return ContactRequest::forBanner($bannerId)
            ->with(['client.profile', 'manager.profile', 'otoBanner'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Получить количество заявок по баннеру
     */
    public function getSubmissionsCount(int $bannerId, ?Carbon $from = null, ?Carbon $to = null): int
    {
        $query = ContactRequest::forBanner($bannerId);

        if ($from) {
            $query->where('created_at', '>=', $from);
        }

        if ($to) {
            $query->where('created_at', '<=', $to);
        }

        return $query->count();
    }

    /**
     * Получить заявки по периодам
     */
    public function getSubmissionsByPeriod(int $bannerId, string $period = 'day'): array
    {
        $groupBy = match($period) {
            'hour' => "DATE_FORMAT(created_at, '%Y-%m-%d %H:00:00')",
            'day' => "DATE(created_at)",
            'month' => "DATE_FORMAT(created_at, '%Y-%m-01')",
            default => "DATE(created_at)",
        };

        return ContactRequest::forBanner($bannerId)
            ->selectRaw("$groupBy as period, COUNT(*) as count")
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->toArray();
    }

    /**
     * Прикрепить менеджера к заявке
     */
    public function attachManager(ContactRequest $submission, int $managerId): bool
    {
        return $submission->update(['manager_id' => $managerId]);
    }

    /**
     * Получить все OTO заявки с пагинацией
     */
    public function getAllOtoSubmissions(int $perPage = 20, array $filters = []): LengthAwarePaginator
    {
        $query = ContactRequest::otoSubmissions()
            ->with(['client.profile', 'manager.profile', 'otoBanner']);

        // Фильтр по баннеру
        if (isset($filters['banner_id'])) {
            $query->where('oto_banner_id', $filters['banner_id']);
        }

        // Фильтр по статусу
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Фильтр по менеджеру
        if (isset($filters['manager_id'])) {
            $query->where('manager_id', $filters['manager_id']);
        }

        // Поиск
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                    ->orWhere('email', 'like', "%$search%")
                    ->orWhere('phone', 'like', "%$search%");
            });
        }

        // Фильтр по дате
        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Получить общее количество OTO заявок
     */
    public function getTotalOtoSubmissionsCount(): int
    {
        return ContactRequest::otoSubmissions()->count();
    }
}
