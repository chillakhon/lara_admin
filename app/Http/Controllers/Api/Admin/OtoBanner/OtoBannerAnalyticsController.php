<?php

namespace App\Http\Controllers\Api\Admin\OtoBanner;

use App\Http\Controllers\Controller;

use App\Models\Oto\OtoBanner;
use App\Services\OtoBanner\OtoBannerAnalyticsService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OtoBannerAnalyticsController extends Controller
{
    public function __construct(
        private readonly OtoBannerAnalyticsService $service,
    ) {}

    /**
     * Получить аналитику по конкретному баннеру
     */
    public function show(Request $request, OtoBanner $otoBanner): JsonResponse
    {
        $from = $request->has('date_from')
            ? Carbon::parse($request->input('date_from'))
            : null;

        $to = $request->has('date_to')
            ? Carbon::parse($request->input('date_to'))
            : null;

        $analytics = $this->service->getBannerAnalytics($otoBanner, $from, $to);

        return response()->json([
            'success' => true,
            'data' => $analytics->toArray(),
        ]);
    }

    /**
     * Получить сводную аналитику по всем баннерам
     */
    public function summary(): JsonResponse
    {
        $analytics = $this->service->getSummaryAnalytics();

        return response()->json([
            'success' => true,
            'data' => $analytics,
        ]);
    }

    /**
     * Получить график по баннеру
     */
    public function chart(Request $request, OtoBanner $otoBanner): JsonResponse
    {
        $period = $request->input('period', 'day'); // hour, day, month

        $chart = $this->service->getBannerChart($otoBanner, $period);

        return response()->json([
            'success' => true,
            'data' => [
                'banner_id' => $otoBanner->id,
                'banner_name' => $otoBanner->name,
                'period' => $period,
                'chart' => $chart,
            ],
        ]);
    }

    /**
     * Экспортировать аналитику баннера
     */
    public function export(OtoBanner $otoBanner): JsonResponse
    {
        $data = $this->service->exportBannerAnalytics($otoBanner);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
}
