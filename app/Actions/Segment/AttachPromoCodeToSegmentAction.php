<?php

namespace App\Actions\Segment;

use App\Models\Segments\Segment;
use App\Repositories\SegmentRepository;
use App\Services\Segment\SegmentPromoCodeSyncService;
use Illuminate\Support\Facades\DB;

class AttachPromoCodeToSegmentAction
{
    public function __construct(
        protected SegmentRepository $repository,
        protected SegmentPromoCodeSyncService $promoCodeSyncService
    ) {}

    /**
     * Выполнить прикрепление промокодов к сегменту
     */
    public function execute(Segment $segment, array $promoCodeIds, bool $autoApply = true): void
    {
        if (empty($promoCodeIds)) {
            throw new \InvalidArgumentException('Не указаны ID промокодов');
        }

        DB::transaction(function () use ($segment, $promoCodeIds, $autoApply) {
            // Прикрепляем промокоды к сегменту
            $this->repository->attachPromoCodes($segment, $promoCodeIds, $autoApply);

            // Синхронизируем промокоды со всеми клиентами сегмента
            $clientIds = $segment->clients()->pluck('clients.id')->toArray();

            if (!empty($clientIds)) {
                $this->promoCodeSyncService->syncSpecificPromoCodeesToClients(
                    $promoCodeIds,
                    $clientIds
                );
            }
        });
    }
}
