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


            // Пресет "all" — берём min/max created_at из таблицы orders.
            // Если в базе нет заказов — фолбэк на последние 6 месяцев.
            $isAllTime = $request->query('preset') === 'all'
                || $request->boolean('all');

            if ($isAllTime) {
                $minCreated = Order::query()->min('created_at');
                $maxCreated = Order::query()->max('created_at');
                $from = $minCreated ? Carbon::parse($minCreated)->startOfDay() : now()->subMonths(5)->startOfMonth();
                $to = $maxCreated ? Carbon::parse($maxCreated)->endOfDay() : now()->endOfDay();
            } else {
                $from = $request->query('from')
                    ? Carbon::parse($request->query('from'))->startOfDay()
                    : now()->subMonths(5)->startOfMonth();
                $to = $request->query('to')
                    ? Carbon::parse($request->query('to'))->endOfDay()
                    : now()->endOfMonth();
            }

            // Авто-выбор гранулярности по длине периода:
            // ≤ 31 день → группируем по дням (метки "dd.mm"),
            // ≤ ~2 года → по месяцам ("Месяц" или "Месяц YYYY", если период пересекает года),
            // больше → по месяцам с явным годом.
            // Можно явно переопределить через ?granularity=day|month.
            $rangeDays = $from->diffInDays($to) + 1;
            $granularity = $request->query('granularity');
            if (!in_array($granularity, ['day', 'month'], true)) {
                $granularity = $rangeDays <= 31 ? 'day' : 'month';
            }

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

            $bucketFormat = $granularity === 'day' ? '%Y-%m-%d' : '%Y-%m';

            // Получаем данные по бакетам (день или месяц) и статусам
            $chartRawData = Order::query()
                ->select([
                    DB::raw("DATE_FORMAT(created_at, \"$bucketFormat\") as bucket"),
                    'status',
                    DB::raw('COUNT(*) as count')
                ])
                ->whereBetween('created_at', [$from, $to])
                ->groupBy('bucket', 'status')
                ->orderBy('bucket')
                ->get();

            // Подготовка данных для графика — учитываем ВСЕ статусы из enum
            $statuses = OrderStatus::values();

            $chartData = ['labels' => []];
            foreach ($statuses as $status) {
                $chartData[$status] = [];
            }

            Carbon::setLocale('ru');

            $buckets = collect();
            $labels = [];
            $crossYears = $from->year !== $to->year;

            if ($granularity === 'day') {
                for ($date = $from->copy()->startOfDay(); $date <= $to; $date->addDay()) {
                    $buckets->push($date->format('Y-m-d'));
                    $labels[] = $date->translatedFormat('d.m');
                }
            } else {
                for ($date = $from->copy()->startOfMonth(); $date <= $to; $date->addMonth()) {
                    $buckets->push($date->format('Y-m'));
                    $labels[] = $crossYears
                        ? $date->translatedFormat('F Y')
                        : $date->translatedFormat('F');
                }
            }

            foreach ($buckets as $bucket) {
                foreach ($statuses as $status) {
                    $row = $chartRawData->first(fn($item) =>
                        $item->bucket === $bucket && $item->status->value === $status
                    );

                    $chartData[$status][] = (int)($row->count ?? 0);
                }
            }

            $chartData['labels'] = $labels;
            $chartData['granularity'] = $granularity;
            $chartData['from'] = $from->toDateString();
            $chartData['to'] = $to->toDateString();

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
