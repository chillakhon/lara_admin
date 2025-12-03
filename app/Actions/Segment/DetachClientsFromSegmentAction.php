<?php

namespace App\Actions\Segment;

use App\Models\Segments\Segment;
use App\Repositories\SegmentRepository;
use App\Services\Segment\SegmentPromoCodeSyncService;
use Illuminate\Support\Facades\DB;

class DetachClientsFromSegmentAction
{
    public function __construct(
        protected SegmentRepository $repository,
        protected SegmentPromoCodeSyncService $promoCodeSyncService
    ) {}

    /**
     * Выполнить удаление клиентов из сегмента
     */
    public function execute(Segment $segment, array $clientIds): void
    {
        if (empty($clientIds)) {
            throw new \InvalidArgumentException('Не указаны ID клиентов');
        }

        DB::transaction(function () use ($segment, $clientIds) {
            // Удаляем промокоды у клиентов
            $this->promoCodeSyncService->removePromoCodesFromClients($segment, $clientIds);

            // Открепляем клиентов от сегмента
            $this->repository->detachClients($segment, $clientIds);
        });
    }
}
