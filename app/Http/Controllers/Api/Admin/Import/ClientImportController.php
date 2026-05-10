<?php

namespace App\Http\Controllers\Api\Admin\Import;

use App\Http\Controllers\Controller;
use App\Services\Import\ClientImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ClientImportController extends Controller
{
    public function __construct(protected ClientImportService $service)
    {
    }

    /**
     * Импорт клиентов из CSV.
     *
     * Принимает CSV (UTF-8 или UTF-16LE с BOM) с tab-разделителем,
     * заголовки соответствуют формату выгрузки InSales.
     */
    public function import(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|max:102400', // до 100 МБ
            'limit' => 'nullable|integer|min:1',
            'dry_run' => 'nullable|boolean',
            'overwrite' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors(),
            ], 422);
        }

        $uploaded = $request->file('file');
        // Сохраняем во временное место — fopen() стрим будет читать оттуда.
        $tmpPath = $uploaded->getRealPath();

        // Чтобы файл точно был доступен по пути после move(), копируем рядом
        // с уникальным суффиксом (на случай если фреймворк удаляет оригинал).
        $storedPath = tempnam(sys_get_temp_dir(), 'client_import_');
        if ($storedPath === false) {
            return response()->json([
                'message' => 'Не удалось подготовить временный файл',
            ], 500);
        }
        copy($tmpPath, $storedPath);

        // Снимаем лимиты на тяжёлые импорты.
        @set_time_limit(0);
        @ini_set('memory_limit', '512M');

        try {
            $stats = $this->service->import($storedPath, [
                'limit' => (int) $request->input('limit', 0),
                'dry_run' => $request->boolean('dry_run'),
                'overwrite' => $request->has('overwrite')
                    ? $request->boolean('overwrite')
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
