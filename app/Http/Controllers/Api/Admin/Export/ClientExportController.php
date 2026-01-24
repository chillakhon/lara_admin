<?php

namespace App\Http\Controllers\Api\Admin\Export;

use App\Http\Controllers\Controller;
use App\Http\Requests\Export\ExportClientsRequest;
use App\Services\Export\ClientExportService;
use Illuminate\Http\JsonResponse;

class ClientExportController extends Controller
{
    protected ClientExportService $exportService;

    public function __construct(ClientExportService $exportService)
    {
        $this->exportService = $exportService;
    }

    /**
     * Экспорт клиентов в CSV
     *
     * @param ExportClientsRequest $request
     * @return \Symfony\Component\HttpFoundation\StreamedResponse|JsonResponse
     */
    public function export(ExportClientsRequest $request)
    {
        $ids = $request->input('ids', []);

        // Проверяем, есть ли клиенты для экспорта
        $query = \App\Models\Client::whereNull('deleted_at');

        if (!empty($ids)) {
            $query->whereIn('id', $ids);
        }

        $count = $query->count();

        // Если нет клиентов - возвращаем ошибку
        if ($count === 0) {
            return response()->json([
                'message' => 'Нет клиентов для экспорта'
            ], 404);
        }

        // Экспортируем с заданным chunk size (5000)
        return $this->exportService->export($ids);
    }
}
