<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\OrderStatus;
use App\Models\Order;
use Carbon\Carbon;
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
    public function stats(Request $request): JsonResponse
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


            $from = $request->query('from') ? Carbon::parse($request->query('from')) : now()->subMonths(5)->startOfMonth();
            $to = $request->query('to') ? Carbon::parse($request->query('to')) : now()->endOfMonth();


            $stats = Order::query()
                ->when($request->has(['from', 'to']), function ($query) use ($from, $to) {
                    $query->whereBetween('created_at', [$from, $to]);
                })
                ->select([
                    'status',
                    DB::raw('COUNT(*) as count'),
                    DB::raw('SUM(total_amount) as total_amount')
                ])
                ->groupBy('status')
                ->get()
                ->keyBy(fn($item) => $item->status->value);

            // Получаем данные по месяцам и статусам
            $chartRawData = Order::query()
                ->select([
                    DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                    'status',
                    DB::raw('COUNT(*) as count')
                ])
                ->whereBetween('created_at', [$from, $to])
                ->groupBy('month', 'status')
                ->orderBy('month')
                ->get();

            // Подготовка данных для графика — учитываем ВСЕ статусы из enum
            $months = collect();
            $statuses = OrderStatus::values();

            $chartData = ['labels' => []];
            foreach ($statuses as $status) {
                $chartData[$status] = [];
            }

            for ($date = clone $from; $date <= $to; $date->addMonth()) {
                $months->push($date->format('Y-m'));
            }

            Carbon::setLocale('ru');

            $monthLabels = $months->map(fn($month) => Carbon::createFromFormat('Y-m', $month)->translatedFormat('F'))->toArray();

            foreach ($months as $month) {
                foreach ($statuses as $status) {
                    $row = $chartRawData->first(fn($item) =>
                        $item->month === $month && $item->status->value === $status
                    );

                    $chartData[$status][] = (int)($row->count ?? 0);
                }
            }

            $chartData['labels'] = $monthLabels;

            // Формируем ответ по всем статусам + общий итог
            $response = [];
            $totalCount = 0;
            $totalAmount = 0.0;

            foreach ($statuses as $status) {
                $count = (int)($stats[$status]->count ?? 0);
                $amount = (float)($stats[$status]->total_amount ?? 0);
                $response[$status] = [
                    'count' => $count,
                    'total_amount' => $amount,
                ];
                $totalCount += $count;
                $totalAmount += $amount;
            }

            $response['total'] = [
                'count' => $totalCount,
                'total_amount' => $totalAmount,
            ];
            $response['chartData'] = $chartData;

            return response()->json($response);

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
