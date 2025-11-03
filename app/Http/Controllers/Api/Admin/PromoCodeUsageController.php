<?php

namespace App\Http\Controllers\Api\Admin;

use App\Helpers\PaginationHelper;
use App\Http\Controllers\Controller;
use App\Models\PromoCode;
use App\Models\PromoCodeUsage;
use App\Services\PromoCode\PromoCodeUsage\PromoCodeUsageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PromoCodeUsageController extends Controller
{
    protected $usageService;

    public function __construct(PromoCodeUsageService $usageService)
    {
        $this->usageService = $usageService;
    }

    /**
     * Получить список всех использований промокодов
     */
    public function index(Request $request)
    {
        $query = PromoCodeUsage::with(['promoCode', 'client', 'order']);

        // Фильтр по промокоду
        if ($request->filled('promo_code_id')) {
            $query->where('promo_code_id', $request->promo_code_id);
        }

        // Фильтр по клиенту
        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        // Фильтр по дате
        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->date_from . ' 00:00:00');
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->date_to . ' 23:59:59');
        }

        // Сортировка
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Пагинация
        $perPage = $request->get('per_page', 15);
        $usages = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $usages->items(),
            'meta' => PaginationHelper::format($usages)
        ]);
    }

    /**
     * Получить общую статистику по конкретному промокоду
     */
    public function getPromoCodeStatistics($promoCodeId)
    {
        try {
            $statistics = $this->usageService->getPromoCodeStatistics($promoCodeId);

            return response()->json([
                'success' => true,
                'data' => $statistics
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при получении статистики: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Получить детальную информацию по использованию промокода
     */
    public function getPromoCodeUsageDetails($promoCodeId, Request $request)
    {
        try {
            $result = $this->usageService->getPromoCodeUsageDetails($promoCodeId, $request);

            return response()->json([
                'success' => true,
                'data' => $result['data'],
                'meta' => $result['meta'],
                'summary' => $result['summary']
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при получении деталей: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Получить топ клиентов по использованию промокода
     */
    public function getTopClients($promoCodeId, Request $request)
    {
        try {
            $limit = $request->get('limit', 10);
            $topClients = $this->usageService->getTopClients($promoCodeId, $limit);

            return response()->json([
                'success' => true,
                'data' => $topClients
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при получении топ клиентов: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Получить статистику использования по периодам
     */
    public function getUsageByPeriod($promoCodeId, Request $request)
    {
        try {
            $period = $request->get('period', 'daily'); // hourly, daily, weekly, monthly
            $dateFrom = $request->get('date_from');
            $dateTo = $request->get('date_to');

            $statistics = $this->usageService->getUsageByPeriod($promoCodeId, $period, $dateFrom, $dateTo);

            return response()->json([
                'success' => true,
                'data' => $statistics
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при получении статистики по периодам: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Получить сводную статистику по всем промокодам
     */
    public function getSummaryStatistics(Request $request)
    {
        try {
            // Базовая статистика по всем промокодам
            $totalPromoCodes = PromoCode::count();
            $activePromoCodes = PromoCode::where('is_active', true)->count();
            $expiredPromoCodes = PromoCode::where('expires_at', '<', now())->count();

            // Статистика использования
            $totalUsages = PromoCodeUsage::count();
            $totalDiscountGiven = PromoCodeUsage::sum('discount_amount');
            $uniqueClients = PromoCodeUsage::distinct('client_id')->count('client_id');

            // Топ промокодов по использованию
            $topPromoCodes = PromoCodeUsage::join('promo_codes', 'promo_code_usages.promo_code_id', '=', 'promo_codes.id')
                ->select(
                    'promo_codes.id',
                    'promo_codes.code',
                    'promo_codes.description',
                    DB::raw('COUNT(*) as usage_count'),
                    DB::raw('SUM(promo_code_usages.discount_amount) as total_discount')
                )
                ->groupBy('promo_codes.id', 'promo_codes.code', 'promo_codes.description')
                ->orderBy('usage_count', 'desc')
                ->limit(5)
                ->get();

            // Статистика по периоду (последние 30 дней)
            $recentUsages = PromoCodeUsage::where('created_at', '>=', now()->subDays(30))
                ->select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('COUNT(*) as count'),
                    DB::raw('SUM(discount_amount) as total_discount')
                )
                ->groupBy('date')
                ->orderBy('date', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'overview' => [
                        'total_promo_codes' => $totalPromoCodes,
                        'active_promo_codes' => $activePromoCodes,
                        'expired_promo_codes' => $expiredPromoCodes,
                        'total_usages' => $totalUsages,
                        'total_discount_given' => round($totalDiscountGiven, 2),
                        'unique_clients' => $uniqueClients,
                        'avg_discount_per_use' => $totalUsages > 0
                            ? round($totalDiscountGiven / $totalUsages, 2)
                            : 0,
                    ],
                    'top_promo_codes' => $topPromoCodes,
                    'recent_activity' => $recentUsages,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при получении сводной статистики: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Экспорт статистики в CSV
     */
    public function exportStatistics($promoCodeId, Request $request)
    {
        try {
            $promoCode = PromoCode::findOrFail($promoCodeId);
            $usages = PromoCodeUsage::with(['client', 'order'])
                ->where('promo_code_id', $promoCodeId)
                ->get();

            $csvData = [];
            $csvData[] = ['Статистика использования промокода: ' . $promoCode->code];
            $csvData[] = [''];
            $csvData[] = [
                'ID использования',
                'Дата использования',
                'Клиент',
                'Email клиента',
                'Номер заказа',
                'Статус заказа',
                'Статус оплаты',
                'Сумма скидки',
                'Сумма заказа',
                'Итоговая сумма'
            ];

            foreach ($usages as $usage) {
                $csvData[] = [
                    $usage->id,
                    $usage->created_at->format('Y-m-d H:i:s'),
                    $usage->client->name ?? 'N/A',
                    $usage->client->email ?? 'N/A',
                    $usage->order->order_number ?? 'N/A',
                    $usage->order->status ?? 'N/A',
                    $usage->order->payment_status ?? 'N/A',
                    $usage->discount_amount,
                    $usage->order->total_amount ?? 0,
                    ($usage->order->total_amount ?? 0) - $usage->discount_amount,
                ];
            }

            $fileName = 'promo_code_' . $promoCode->code . '_statistics_' . date('Y-m-d_H-i-s') . '.csv';
            $headers = [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            ];

            $callback = function () use ($csvData) {
                $file = fopen('php://output', 'w');
                fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM для корректного отображения UTF-8

                foreach ($csvData as $row) {
                    fputcsv($file, $row, ';');
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при экспорте: ' . $e->getMessage()
            ], 500);
        }
    }
}
