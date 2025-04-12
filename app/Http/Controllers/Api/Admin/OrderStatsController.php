<?php

namespace App\Http\Controllers\Api\Admin;

use App\Models\Order;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

class OrderStatsController extends Controller
{
    /**
     * Получить статистику заказов по статусам
     *
     * @OA\Get(
     *     path="/api/orders/stats",
     *     summary="Статистика заказов",
     *     description="Возвращает агрегированную статистику по заказам: количество и общую сумму для каждого статуса",
     *     tags={"Orders"},
     *     @OA\Response(
     *         response=200,
     *         description="Успешный ответ",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="new",
     *                 type="object",
     *                 @OA\Property(property="count", type="integer", example=105),
     *                 @OA\Property(property="total_amount", type="number", format="float", example=584200.50)
     *             ),
     *             @OA\Property(
     *                 property="processing",
     *                 type="object",
     *                 @OA\Property(property="count", type="integer", example=42),
     *                 @OA\Property(property="total_amount", type="number", format="float", example=215700.00)
     *             ),
     *             @OA\Property(
     *                 property="approved",
     *                 type="object",
     *                 @OA\Property(property="count", type="integer", example=8),
     *                 @OA\Property(property="total_amount", type="number", format="float", example=43200.00)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Ошибка сервера",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Internal Server Error"),
     *             @OA\Property(property="message", type="string", example="Не удалось получить статистику")
     *         )
     *     )
     * )
     */
    public function stats(): JsonResponse
    {
        try {
            if (!Schema::hasTable('orders')) {
                throw new \RuntimeException('Таблица orders не существует');
            }

            $requiredColumns = ['status', 'total_amount', 'created_at'];
            foreach ($requiredColumns as $column) {
                if (!Schema::hasColumn('orders', $column)) {
                    throw new \RuntimeException("Отсутствует обязательное поле: $column");
                }
            }

            // Основная статистика по статусам
            $stats = Order::query()
                ->select([
                    'status',
                    DB::raw('COUNT(*) as count'),
                    DB::raw('SUM(total_amount) as total_amount')
                ])
                ->groupBy('status')
                ->get()
                ->keyBy('status');

            // Подготовка данных для графика за последние 6 месяцев
            $startDate = now()->subMonths(5)->startOfMonth();
            $endDate = now()->endOfMonth();

            // Получаем данные по месяцам и статусам
            $chartRawData = Order::query()
                ->select([
                    DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                    'status',
                    DB::raw('COUNT(*) as count')
                ])
                ->whereBetween('created_at', [$startDate, $endDate])
                ->groupBy('month', 'status')
                ->orderBy('month')
                ->get();

            // Формируем массивы для графика
            $months = collect();
            $statuses = ['new', 'processing', 'approved'];
            $chartData = [
                'labels' => [],
                'new' => [],
                'processing' => [],
                'approved' => [],
            ];

            // Собираем список месяцев
            for ($date = clone $startDate; $date <= $endDate; $date->addMonth()) {
                $months->push($date->format('Y-m'));
            }

            \Carbon\Carbon::setLocale('ru');

            $monthLabels = $months->map(function ($month) {
                return \Carbon\Carbon::createFromFormat('Y-m', $month)->translatedFormat('F');
            })->toArray();

            // Заполняем данные по статусам
            foreach ($months as $month) {
                foreach ($statuses as $status) {
                    $count = $chartRawData
                        ->firstWhere(fn ($item) => $item->month === $month && $item->status === $status)
                        ->count ?? 0;

                    $chartData[$status][] = (int) $count;
                }
            }

            $chartData['labels'] = $monthLabels;

            return response()->json([
                'new' => [
                    'count' => (int) ($stats['new']->count ?? 0),
                    'total_amount' => (float) ($stats['new']->total_amount ?? 0)
                ],
                'processing' => [
                    'count' => (int) ($stats['processing']->count ?? 0),
                    'total_amount' => (float) ($stats['processing']->total_amount ?? 0)
                ],
                'approved' => [
                    'count' => (int) ($stats['approved']->count ?? 0),
                    'total_amount' => (float) ($stats['approved']->total_amount ?? 0)
                ],
                'chartData' => $chartData
            ]);

        } catch (\RuntimeException $e) {
            Log::error("Validation error in Order stats: " . $e->getMessage());
            return response()->json([
                'error' => 'Validation Error',
                'message' => $e->getMessage()
            ], 400);
        } catch (QueryException $e) {
            Log::error("Database error in Order stats: " . $e->getMessage());
            return response()->json([
                'error' => 'Database Error',
                'message' => 'Ошибка при запросе к базе данных'
            ], 500);
        } catch (\Exception $e) {
            Log::error("Unexpected error in Order stats: " . $e->getMessage());
            return response()->json([
                'error' => 'Internal Server Error',
                'message' => 'Не удалось получить статистику'
            ], 500);
        }
    }
}
