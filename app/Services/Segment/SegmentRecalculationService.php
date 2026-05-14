<?php

namespace App\Services\Segment;

use App\DTOs\Segment\SegmentConditionsDTO;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Client;
use App\Models\Order;
use App\Models\Segments\Segment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SegmentRecalculationService
{
    public function __construct(
        protected SegmentPromoCodeSyncService $promoCodeSyncService
    )
    {
    }

    /**
     * Пересчитать клиентов сегмента на основе условий
     */
    public function recalculate(Segment $segment): void
    {
        $conditions = SegmentConditionsDTO::fromArray($segment->conditions);

        if (!$conditions || !$conditions->hasConditions()) {
            return;
        }

        DB::transaction(function () use ($segment, $conditions) {
            // Получаем текущих клиентов сегмента
            $currentClientIds = $segment->clients()->pluck('clients.id')->toArray();

            // Находим клиентов, соответствующих условиям
            $newClientIds = $this->findClientsMatchingConditions($conditions);

            // Клиенты для добавления
            $clientsToAdd = array_diff($newClientIds, $currentClientIds);

            // Клиенты для удаления
            $clientsToRemove = array_diff($currentClientIds, $newClientIds);

            // Добавляем новых клиентов
            if (!empty($clientsToAdd)) {
                $segment->clients()->attach($clientsToAdd, [
                    'added_at' => now()
                ]);

                // Синхронизируем промокоды с новыми клиентами
                $this->promoCodeSyncService->syncPromoCodeesToClients($segment, $clientsToAdd);
            }

            // Удаляем клиентов, не соответствующих условиям
            if (!empty($clientsToRemove)) {
                // Удаляем промокоды у клиентов
                $this->promoCodeSyncService->removePromoCodesFromClients($segment, $clientsToRemove);

                // Открепляем клиентов
                $segment->clients()->detach($clientsToRemove);
            }

            // Обновляем время последнего пересчёта
            $segment->markAsRecalculated();
        });
    }

    /**
     * Найти клиентов, соответствующих условиям
     */
    protected function findClientsMatchingConditions(SegmentConditionsDTO $conditions): array
    {
        $query = Client::query()
            ->whereNotNull('verified_at'); // Только верифицированные клиенты

        // Подзапрос для расчёта статистики по заказам
        $query->select('clients.id')
            ->leftJoin('orders', function ($join) use ($conditions) {
                $join->on('clients.id', '=', 'orders.client_id')
                    ->where('orders.status', OrderStatus::DELIVERED)
                    ->where('orders.payment_status', PaymentStatus::PAID)
                    ->whereNull('orders.deleted_at');

                // Фильтр по периоду
                if ($startDate = $conditions->getStartDate()) {
                    $join->where('orders.created_at', '>=', $startDate);
                }
            })
            ->groupBy('clients.id');

        // Условие: минимальное количество заказов
        if ($conditions->minOrdersCount !== null) {
            $query->havingRaw('COUNT(orders.id) >= ?', [$conditions->minOrdersCount]);
        }

        // Условие: максимальное количество заказов
        if ($conditions->maxOrdersCount !== null) {
            $query->havingRaw('COUNT(orders.id) <= ?', [$conditions->maxOrdersCount]);
        }

        // Условие: минимальная сумма заказов
        if ($conditions->minTotalAmount !== null) {
            $query->havingRaw('COALESCE(SUM(orders.total_amount), 0) >= ?', [$conditions->minTotalAmount]);
        }

        // Условие: минимальный средний чек
        if ($conditions->minAverageCheck !== null) {
            $query->havingRaw(
                'COALESCE(SUM(orders.total_amount) / NULLIF(COUNT(orders.id), 0), 0) >= ?',
                [$conditions->minAverageCheck]
            );
        }

        return $query->pluck('clients.id')->toArray();
    }

    /**
     * Пересчитать все активные сегменты
     */
    public function recalculateAll(): void
    {
        $segments = Segment::active()
            ->where('recalculate_frequency', 'on_view')
            ->get();

        foreach ($segments as $segment) {
            $this->recalculate($segment);
        }
    }
}
