<?php

namespace App\Services\Segment;

use App\DTOs\Segment\CreateSegmentDTO;
use App\DTOs\Segment\UpdateSegmentDTO;
use App\Models\Segments\Segment;
use App\Repositories\SegmentRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class SegmentService
{
    public function __construct(
        protected SegmentRepository $segmentRepository,
        protected SegmentRecalculationService $recalculationService,
        protected SegmentPromoCodeSyncService $promoCodeSyncService
    ) {}

    /**
     * Получить все сегменты
     */
    public function getAll(array $filters = []): Collection
    {
        return $this->segmentRepository->getAll($filters);
    }

    /**
     * Получить сегмент по ID с пересчётом (если нужно)
     */
    public function getById(int $id): Segment
    {
        $segment = $this->segmentRepository->findById($id);

        // Пересчитать клиентов, если нужно
        if ($segment->needsRecalculation()) {
            $this->recalculationService->recalculate($segment);
        }

        return $segment->fresh(['clients', 'promoCodes']);
    }

    /**
     * Создать новый сегмент
     */
    public function create(CreateSegmentDTO $dto): Segment
    {
        return DB::transaction(function () use ($dto) {
            $segment = $this->segmentRepository->create($dto->toArray());

            // Если есть условия, сразу пересчитываем клиентов
            if (!empty($dto->conditions)) {
                $this->recalculationService->recalculate($segment);
            }

            return $segment->fresh(['clients', 'promoCodes']);
        });
    }

    /**
     * Обновить сегмент
     */
    public function update(Segment $segment, UpdateSegmentDTO $dto): Segment
    {
        return DB::transaction(function () use ($segment, $dto) {
            $updated = $this->segmentRepository->update($segment, $dto->toArray());

            // Если изменились условия, пересчитываем клиентов
            if (isset($dto->conditions)) {
                $this->recalculationService->recalculate($updated);
            }

            return $updated->fresh(['clients', 'promoCodes']);
        });
    }

    /**
     * Удалить сегмент
     */
    public function delete(Segment $segment): bool
    {
        return DB::transaction(function () use ($segment) {
            // Сначала открепляем все промокоды от клиентов сегмента
            $this->promoCodeSyncService->detachAllPromoCodesFromSegment($segment);

            // Удаляем сегмент (каскадно удалятся связи)
            return $this->segmentRepository->delete($segment);
        });
    }

    /**
     * Добавить клиентов в сегмент вручную
     */
    public function attachClients(Segment $segment, array $clientIds): void
    {
        DB::transaction(function () use ($segment, $clientIds) {
            $this->segmentRepository->attachClients($segment, $clientIds);

            // Синхронизируем промокоды с новыми клиентами
            $this->promoCodeSyncService->syncPromoCodeesToClients($segment, $clientIds);
        });
    }

    /**
     * Удалить клиентов из сегмента
     */
    public function detachClients(Segment $segment, array $clientIds): void
    {
        DB::transaction(function () use ($segment, $clientIds) {
            // Сначала удаляем промокоды у клиентов
            $this->promoCodeSyncService->removePromoCodesFromClients($segment, $clientIds);

            // Затем открепляем клиентов от сегмента
            $this->segmentRepository->detachClients($segment, $clientIds);
        });
    }

    /**
     * Прикрепить промокоды к сегменту
     */
    public function attachPromoCodes(Segment $segment, array $promoCodeIds, bool $autoApply = true): void
    {
        DB::transaction(function () use ($segment, $promoCodeIds, $autoApply) {
            // Прикрепляем промокоды к сегменту
            $this->segmentRepository->attachPromoCodes($segment, $promoCodeIds, $autoApply);

            // Синхронизируем промокоды со всеми клиентами сегмента
            $clientIds = $segment->clients()->pluck('clients.id')->toArray();
            $this->promoCodeSyncService->syncSpecificPromoCodeesToClients(
                $promoCodeIds,
                $clientIds
            );
        });
    }

    /**
     * Открепить промокоды от сегмента
     */
    public function detachPromoCodes(Segment $segment, array $promoCodeIds): void
    {
        DB::transaction(function () use ($segment, $promoCodeIds) {
            // Удаляем промокоды у всех клиентов сегмента
            $clientIds = $segment->clients()->pluck('clients.id')->toArray();
            $this->promoCodeSyncService->removeSpecificPromoCodesFromClients(
                $promoCodeIds,
                $clientIds
            );

            // Открепляем промокоды от сегмента
            $this->segmentRepository->detachPromoCodes($segment, $promoCodeIds);
        });
    }

    /**
     * Переключить активность сегмента
     */
    public function toggleActive(Segment $segment): Segment
    {
        return $this->segmentRepository->update($segment, [
            'is_active' => !$segment->is_active
        ]);
    }
}
