<?php

namespace App\Actions\Segment;

use App\Models\Segments\Segment;
use App\Services\Segment\SegmentPromoCodeSyncService;
use Illuminate\Support\Facades\DB;

class DeleteSegmentAction
{
    public function __construct(
        protected SegmentPromoCodeSyncService $promoCodeSyncService
    ) {}

    /**
     * Выполнить удаление сегмента
     */
    public function execute(Segment $segment): bool
    {
        return DB::transaction(function () use ($segment) {
            // Открепляем все промокоды от клиентов сегмента
            $this->promoCodeSyncService->detachAllPromoCodesFromSegment($segment);

            // Удаляем сегмент (каскадно удалятся client_segment и promo_code_segment)
            return $segment->delete();
        });
    }
}
