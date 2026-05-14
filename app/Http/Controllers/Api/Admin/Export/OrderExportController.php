<?php

namespace App\Http\Controllers\Api\Admin\Export;

use App\Http\Controllers\Controller;
use App\Http\Requests\Export\ExportOrdersRequest;
use App\Services\Export\OrderExportService;
use Illuminate\Http\JsonResponse;

class OrderExportController extends Controller
{
    protected OrderExportService $exportService;

    public function __construct(OrderExportService $exportService)
    {
        $this->exportService = $exportService;
    }

    /**
     * Экспорт заказов в CSV
     *
     * @param ExportOrdersRequest $request
     * @return \Symfony\Component\HttpFoundation\StreamedResponse|JsonResponse
     */
    public function export(ExportOrdersRequest $request)
    {
        $ids = $request->input('ids', []);

        // Проверяем, есть ли заказы для экспорта
        $query = \App\Models\Order::whereNull('deleted_at');

        if (!empty($ids)) {
            $query->whereIn('id', $ids);
        }

        $count = $query->count();

        // Если нет заказов - возвращаем ошибку
        if ($count === 0) {
            return response()->json([
                'message' => 'Нет заказов для экспорта'
            ], 404);
        }

        // Экспортируем с заданным chunk size (5000)
        return $this->exportService->export($ids);
    }
}
