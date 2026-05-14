<?php

namespace App\Actions\Segment;

use App\Models\Segments\Segment;
use App\Repositories\SegmentRepository;
use App\Services\Segment\SegmentPromoCodeSyncService;
use Illuminate\Support\Facades\DB;

class AttachClientsToSegmentAction
{
    public function __construct(
        protected SegmentRepository $repository,
        protected SegmentPromoCodeSyncService $promoCodeSyncService
    ) {}

    /**
     * Выполнить добавление клиентов в сегмент
     */
    public function execute(Segment $segment, array $clientIds): void
    {
        if (empty($clientIds)) {
            throw new \InvalidArgumentException('Не указаны ID клиентов');
        }

        DB::transaction(function () use ($segment, $clientIds) {
            // Прикрепляем клиентов к сегменту
            $this->repository->attachClients($segment, $clientIds);

            // Синхронизируем промокоды с новыми клиентами
            $this->promoCodeSyncService->syncPromoCodeesToClients($segment, $clientIds);
        });
    }
}
