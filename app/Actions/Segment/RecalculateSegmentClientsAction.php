<?php

namespace App\Actions\Segment;

use App\Models\Segments\Segment;
use App\Services\Segment\SegmentRecalculationService;

class RecalculateSegmentClientsAction
{
    public function __construct(
        protected SegmentRecalculationService $recalculationService
    ) {}

    /**
     * Выполнить пересчёт клиентов сегмента
     */
    public function execute(Segment $segment): void
    {
        $this->recalculationService->recalculate($segment);
    }

    /**
     * Пересчитать все сегменты
     */
    public function executeAll(): void
    {
        $this->recalculationService->recalculateAll();
    }
}
