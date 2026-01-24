<?php

namespace App\Http\Controllers\Api\Admin\Export;

use App\Http\Controllers\Controller;
use App\Http\Requests\Export\ExportReviewsRequest;
use App\Services\Export\ReviewExportService;
use Illuminate\Http\JsonResponse;

class ReviewExportController extends Controller
{
    protected ReviewExportService $exportService;

    public function __construct(ReviewExportService $exportService)
    {
        $this->exportService = $exportService;
    }

    /**
     * Экспорт отзывов в CSV
     *
     * @param ExportReviewsRequest $request
     * @return \Symfony\Component\HttpFoundation\StreamedResponse|JsonResponse
     */
    public function export(ExportReviewsRequest $request)
    {
        $ids = $request->input('ids', []);

        // Проверяем, есть ли отзывы для экспорта
        $query = \App\Models\Review\Review::whereNull('deleted_at');

        if (!empty($ids)) {
            $query->whereIn('id', $ids);
        }

        $count = $query->count();

        // Если нет отзывов - возвращаем ошибку
        if ($count === 0) {
            return response()->json([
                'message' => 'Нет отзывов для экспорта'
            ], 404);
        }

        // Экспортируем с заданным chunk size (5000)
        return $this->exportService->export($ids);
    }
}
