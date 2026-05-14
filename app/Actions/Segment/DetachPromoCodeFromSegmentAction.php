<?php

namespace App\Actions\Segment;

use App\Models\Segments\Segment;
use App\Repositories\SegmentRepository;
use App\Services\Segment\SegmentPromoCodeSyncService;
use Illuminate\Support\Facades\DB;

class DetachPromoCodeFromSegmentAction
{
    public function __construct(
        protected SegmentRepository $repository,
        protected SegmentPromoCodeSyncService $promoCodeSyncService
    ) {}

    /**
     * Выполнить открепление промокодов от сегмента
     */
    public function execute(Segment $segment, array $promoCodeIds): void
    {
        if (empty($promoCodeIds)) {
            throw new \InvalidArgumentException('Не указаны ID промокодов');
        }

        DB::transaction(function () use ($segment, $promoCodeIds) {
            // Удаляем промокоды у всех клиентов сегмента
            $clientIds = $segment->clients()->pluck('clients.id')->toArray();

            if (!empty($clientIds)) {
                $this->promoCodeSyncService->removeSpecificPromoCodesFromClients(
                    $promoCodeIds,
                    $clientIds
                );
            }

            // Открепляем промокоды от сегмента
            $this->repository->detachPromoCodes($segment, $promoCodeIds);
        });
    }
}
