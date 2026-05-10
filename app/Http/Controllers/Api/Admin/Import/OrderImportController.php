<?php

namespace App\Http\Controllers\Api\Admin\Import;

use App\Http\Controllers\Controller;
use App\Services\Import\OrderImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OrderImportController extends Controller
{
    public function __construct(protected OrderImportService $service)
    {
    }

    /**
     * Импорт заказов из CSV (выгрузка InSales).
     *
     * UTF-8 или UTF-16LE с BOM, tab-разделитель, заголовки соответствуют
     * формату orders-DD.MM.YYYY.csv.
     */
    public function import(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|max:512000', // до 500 МБ
            'limit' => 'nullable|integer|min:1',
            'dry_run' => 'nullable|boolean',
            'overwrite' => 'nullable|boolean',
            'import_history' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors(),
            ], 422);
        }

        $uploaded = $request->file('file');
        $tmpPath = $uploaded->getRealPath();

        // На случай, если фреймворк удалит оригинал после move().
        $storedPath = tempnam(sys_get_temp_dir(), 'order_import_');
        if ($storedPath === false) {
            return response()->json([
                'message' => 'Не удалось подготовить временный файл',
            ], 500);
        }
        copy($tmpPath, $storedPath);

        @set_time_limit(0);
        @ini_set('memory_limit', '1024M');

        try {
            $stats = $this->service->import($storedPath, [
                'limit' => (int) $request->input('limit', 0),
                'dry_run' => $request->boolean('dry_run'),
                'overwrite' => $request->has('overwrite')
                    ? $request->boolean('overwrite')
                    : true,
                'import_history' => $request->has('import_history')
                    ? $request->boolean('import_history')
                    : true,
            ]);
        } finally {
            if (is_file($storedPath)) {
                @unlink($storedPath);
            }
        }

        return response()->json([
            'message' => 'Импорт завершён',
            'stats' => $stats,
        ]);
    }
}
