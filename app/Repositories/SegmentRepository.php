<?php

namespace App\Repositories;

use App\DTOs\Segment\SegmentClientFilterDTO;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Client;
use App\Models\Order;
use App\Models\Segments\Segment;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class SegmentRepository
{
    /**
     * Получить все сегменты с фильтрацией
     */
    public function getAll(array $filters = []): Collection
    {
        $query = Segment::query()->with(['clients', 'promoCodes']);

        // Фильтр по активности
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        // Поиск по названию
        if (isset($filters['search'])) {
            $query->where('name', 'like', '%' . $filters['search'] . '%');
        }

        // Сортировка
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDirection = $filters['sort_direction'] ?? 'desc';
        $query->orderBy($sortBy, $sortDirection);

        return $query->get();
    }


    /**
     * Получить доступных клиентов для добавления в сегмент
     * Возвращает только базовую информацию: ID, email, profile
     */
    public function getAvailableClients(Segment $segment, SegmentClientFilterDTO $filters): LengthAwarePaginator
    {
        $query = Client::query()
            ->with(['profile'])
            ->select('clients.*');

        // Исключаем клиентов, которые УЖЕ в сегменте
        $query->whereNotIn('clients.id', function ($subQuery) use ($segment) {
            $subQuery->select('client_id')
                ->from('client_segment')
                ->where('segment_id', $segment->id);
        });

        // Фильтр по поиску (имя, email, телефон)
        if ($filters->search) {
            $query->where(function ($q) use ($filters) {
                $q->where('email', 'like', '%' . $filters->search . '%')
                    ->orWhereHas('profile', function ($profileQuery) use ($filters) {
                        $profileQuery->where('first_name', 'like', '%' . $filters->search . '%')
                            ->orWhere('last_name', 'like', '%' . $filters->search . '%')
                            ->orWhere('phone', 'like', '%' . $filters->search . '%');
                    });
            });
        }

        // Сортировка
        $query->orderBy('created_at', 'desc');

        return $query->paginate($filters->perPage);
    }


    /**
     * Получить сегменты с пагинацией
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Segment::query()
            ->withCount('clients', 'promoCodes');

        // Фильтр по активности
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        // Поиск по названию
        if (isset($filters['search'])) {
            $query->where('name', 'like', '%' . $filters['search'] . '%');
        }

        // Сортировка
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDirection = $filters['sort_direction'] ?? 'desc';
        $query->orderBy($sortBy, $sortDirection);

        return $query->paginate($perPage);
    }

    /**
     * Найти сегмент по ID
     */
    public function findById(int $id): Segment
    {
        return Segment::with(['clients', 'promoCodes'])->findOrFail($id);
    }

    /**
     * Создать новый сегмент
     */
    public function create(array $data): Segment
    {
        return Segment::create($data);
    }

    /**
     * Обновить сегмент
     */
    public function update(Segment $segment, array $data): Segment
    {
        $segment->update($data);
        return $segment->fresh();
    }

    /**
     * Удалить сегмент
     */
    public function delete(Segment $segment): bool
    {
        return $segment->delete();
    }

    /**
     * Прикрепить клиентов к сегменту
     */
    public function attachClients(Segment $segment, array $clientIds): void
    {
        $attachData = [];
        $now = now();

        foreach ($clientIds as $clientId) {
            $attachData[$clientId] = [
                'added_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        $segment->clients()->syncWithoutDetaching($attachData);
    }

    /**
     * Открепить клиентов от сегмента
     */
    public function detachClients(Segment $segment, array $clientIds): void
    {
        $segment->clients()->detach($clientIds);
    }

    /**
     * Прикрепить промокоды к сегменту
     */
    public function attachPromoCodes(Segment $segment, array $promoCodeIds, bool $autoApply = true): void
    {
        $attachData = [];
        $now = now();

        foreach ($promoCodeIds as $promoCodeId) {
            $attachData[$promoCodeId] = [
                'auto_apply' => $autoApply,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        $segment->promoCodes()->syncWithoutDetaching($attachData);
    }

    /**
     * Открепить промокоды от сегмента
     */
    public function detachPromoCodes(Segment $segment, array $promoCodeIds): void
    {
        $segment->promoCodes()->detach($promoCodeIds);
    }

    /**
     * Получить клиентов сегмента с фильтрацией и пагинацией
     */
    public function getSegmentClients(Segment $segment, SegmentClientFilterDTO $filters): LengthAwarePaginator
    {
        $query = $segment->clients()
            ->with([
                'profile',
                'tags:id,name',
                'orders' => function ($q) {
                    $q->where('status', OrderStatus::DELIVERED)
                        ->where('payment_status', PaymentStatus::PAID);
                }])
            ->select('clients.*');

        // Добавляем вычисляемые поля для статистики
        $query->selectRaw('
            (SELECT COUNT(*) FROM orders
             WHERE orders.client_id = clients.id
             AND orders.status = ?
             AND orders.payment_status = ?
             AND orders.deleted_at IS NULL
            ) as orders_count',
            [OrderStatus::DELIVERED, PaymentStatus::PAID]
        );

        $query->selectRaw('
            COALESCE((SELECT SUM(total_amount) FROM orders
             WHERE orders.client_id = clients.id
             AND orders.status = ?
             AND orders.payment_status = ?
             AND orders.deleted_at IS NULL
            ), 0) as total_amount',
            [OrderStatus::DELIVERED, PaymentStatus::PAID]
        );

        $query->selectRaw('
            COALESCE((SELECT SUM(total_amount) / NULLIF(COUNT(*), 0) FROM orders
             WHERE orders.client_id = clients.id
             AND orders.status = ?
             AND orders.payment_status = ?
             AND orders.deleted_at IS NULL
            ), 0) as average_check',
            [OrderStatus::DELIVERED, PaymentStatus::PAID]
        );

        // Фильтр по поиску (имя, телефон)
        if ($filters->search) {
            $search = $filters->search;

            $query->where(function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                    ->orWhereHas('profile', function ($profileQuery) use ($search) {
                        $profileQuery->where(function ($subQuery) use ($search) {
                            $subQuery->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%")
                                ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"]);
                        });
                    });
            });
        }

        // Фильтр по периоду заказов
        if ($filters->periodFrom || $filters->periodTo) {
            $query->whereHas('orders', function ($q) use ($filters) {
                if ($filters->periodFrom) {
                    $q->where('created_at', '>=', $filters->periodFrom);
                }
                if ($filters->periodTo) {
                    $q->where('created_at', '<=', $filters->periodTo);
                }
                $q->where('status', OrderStatus::DELIVERED)
                    ->where('payment_status', PaymentStatus::PAID);
            });
        }

        // Фильтр по сумме покупок
        if ($filters->minTotalAmount !== null || $filters->maxTotalAmount !== null) {
            $query->having(function ($q) use ($filters) {
                if ($filters->minTotalAmount !== null) {
                    $q->havingRaw('total_amount >= ?', [$filters->minTotalAmount]);
                }
                if ($filters->maxTotalAmount !== null) {
                    $q->havingRaw('total_amount <= ?', [$filters->maxTotalAmount]);
                }
            });
        }

        // Сортировка
        $allowedSortColumns = ['created_at', 'total_amount', 'average_check', 'orders_count'];
        if (in_array($filters->sortBy, $allowedSortColumns)) {
            $query->orderBy($filters->sortBy, $filters->sortDirection);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        return $query->paginate($filters->perPage);
    }

    /**
     * Получить статистику сегмента
     */
    public function getSegmentStatistics(Segment $segment): array
    {
        $stats = DB::table('clients')
            ->join('client_segment', 'clients.id', '=', 'client_segment.client_id')
            ->leftJoin('orders', function ($join) {
                $join->on('clients.id', '=', 'orders.client_id')
                    ->where('orders.status', OrderStatus::DELIVERED)
                    ->where('orders.payment_status', PaymentStatus::PAID)
                    ->whereNull('orders.deleted_at');
            })
            ->where('client_segment.segment_id', $segment->id)
            ->selectRaw('
                COUNT(DISTINCT clients.id) as clients_count,
                COALESCE(SUM(orders.total_amount), 0) as total_amount,
                COUNT(orders.id) as total_orders,
                COALESCE(SUM(orders.total_amount) / NULLIF(COUNT(orders.id), 0), 0) as average_check
            ')
            ->first();

        return [
            'clients_count' => (int)$stats->clients_count,
            'total_amount' => (float)$stats->total_amount,
            'total_orders' => (int)$stats->total_orders,
            'average_check' => (float)$stats->average_check,
        ];
    }

    /**
     * Получить детальную статистику по клиентам сегмента
     */
    public function getClientsBreakdown(Segment $segment): array
    {
        return DB::table('clients')
            ->join('client_segment', 'clients.id', '=', 'client_segment.client_id')
            ->leftJoin('user_profiles', 'clients.id', '=', 'user_profiles.client_id')
            ->leftJoin('orders', function ($join) {
                $join->on('clients.id', '=', 'orders.client_id')
                    ->where('orders.status', OrderStatus::DELIVERED)
                    ->where('orders.payment_status', PaymentStatus::PAID)
                    ->whereNull('orders.deleted_at');
            })
            ->where('client_segment.segment_id', $segment->id)
            ->groupBy('clients.id', 'clients.email', 'user_profiles.first_name', 'user_profiles.last_name')
            ->selectRaw('
                clients.id,
                clients.email,
                CONCAT(COALESCE(user_profiles.first_name, ""), " ", COALESCE(user_profiles.last_name, "")) as full_name,
                COUNT(orders.id) as orders_count,
                COALESCE(SUM(orders.total_amount), 0) as total_amount,
                COALESCE(SUM(orders.total_amount) / NULLIF(COUNT(orders.id), 0), 0) as average_check
            ')
            ->orderByDesc('total_amount')
            ->limit(10)
            ->get()
            ->toArray();
    }

    /**
     * Проверить, существует ли сегмент с таким именем
     */
    public function existsByName(string $name, ?int $excludeId = null): bool
    {
        $query = Segment::where('name', $name);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
}
