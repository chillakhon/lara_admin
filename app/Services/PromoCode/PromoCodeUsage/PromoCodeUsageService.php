<?php

namespace App\Services\PromoCode\PromoCodeUsage;

use App\Helpers\PaginationHelper;
use App\Models\PromoCode;
use App\Models\PromoCodeUsage;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PromoCodeUsageService
{
    /**
     * Получить общую статистику по промокоду
     */
    public function getPromoCodeStatistics($promoCodeId)
    {
        $promoCode = PromoCode::findOrFail($promoCodeId);

        // Базовая статистика использования
        $stats = PromoCodeUsage::where('promo_code_usages.promo_code_id', $promoCodeId)
            ->selectRaw('
                COUNT(*) as times_uses,
                COUNT(DISTINCT client_id) as unique_clients,
                SUM(discount_amount) as total_discount_given,
                AVG(discount_amount) as avg_discount_per_usage,
                MIN(created_at) as first_used_at,
                MAX(created_at) as last_used_at
            ')
            ->first();

        // Статистика по статусам заказов
        $orderStatusStats = PromoCodeUsage::where('promo_code_usages.promo_code_id', $promoCodeId)
            ->join('orders', 'promo_code_usages.order_id', '=', 'orders.id')
            ->select('orders.status', DB::raw('COUNT(*) as count'))
            ->groupBy('orders.status')
            ->get()
            ->pluck('count', 'status')
            ->toArray();

        // Статистика по статусам оплаты
        $paymentStatusStats = PromoCodeUsage::where('promo_code_usages.promo_code_id', $promoCodeId)
            ->join('orders', 'promo_code_usages.order_id', '=', 'orders.id')
            ->select('orders.payment_status', DB::raw('COUNT(*) as count'))
            ->groupBy('orders.payment_status')
            ->get()
            ->pluck('count', 'payment_status')
            ->toArray();

        // Общая сумма продаж с применением промокода
        $totalSales = PromoCodeUsage::where('promo_code_usages.promo_code_id', $promoCodeId)
            ->join('orders', 'promo_code_usages.order_id', '=', 'orders.id')
            ->sum('orders.total_amount');

        // Сумма продаж по статусам оплаты
        $salesByPaymentStatus = PromoCodeUsage::where('promo_code_usages.promo_code_id', $promoCodeId)
            ->join('orders', 'promo_code_usages.order_id', '=', 'orders.id')
            ->select(
                'orders.payment_status',
                DB::raw('SUM(orders.total_amount) as total_amount'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('orders.payment_status')
            ->get();

        return [
            'promo_code' => [
                'id' => $promoCode->id,
                'code' => $promoCode->code,
                'description' => $promoCode->description,
                'discount_type' => $promoCode->discount_type,
                'discount_amount' => $promoCode->discount_amount,
                'is_active' => $promoCode->is_active,
                'max_uses' => $promoCode->max_uses,
                'starts_at' => $promoCode->starts_at,
                'expires_at' => $promoCode->expires_at,
            ],
            'statistics' => [
                'times_uses' => $stats->times_uses ?? 0,
                'unique_clients' => $stats->unique_clients ?? 0,
                'total_discount_given' => $stats->total_discount_given ?? 0,
                'avg_discount_per_usage' => round($stats->avg_discount_per_usage ?? 0, 2),
                'first_used_at' => $stats->first_used_at,
                'last_used_at' => $stats->last_used_at,
                'usage_percentage' => $promoCode->max_uses
                    ? round(($stats->times_uses ?? 0) / $promoCode->max_uses * 100, 2)
                    : null,
            ],
            'order_status_breakdown' => [
                'new' => $orderStatusStats['new'] ?? 0,
                'processing' => $orderStatusStats['processing'] ?? 0,
                'approved' => $orderStatusStats['approved'] ?? 0,
                'shipped' => $orderStatusStats['shipped'] ?? 0,
                'completed' => $orderStatusStats['completed'] ?? 0,
                'return_in_progress' => $orderStatusStats['return_in_progress'] ?? 0,
                'cancelled' => $orderStatusStats['cancelled'] ?? 0,
                'returned' => $orderStatusStats['returned'] ?? 0,
            ],
            'payment_status_breakdown' => [
                'pending' => $paymentStatusStats['pending'] ?? 0,
                'paid' => $paymentStatusStats['paid'] ?? 0,
                'failed' => $paymentStatusStats['failed'] ?? 0,
                'refunded' => $paymentStatusStats['refunded'] ?? 0,
            ],
            'sales_summary' => [
                'total_sales' => round($totalSales ?? 0, 2),
                'by_payment_status' => $salesByPaymentStatus->map(function ($item) {
                    return [
                        'status' => $item->payment_status,
                        'total_amount' => round($item->total_amount, 2),
                        'order_count' => $item->count,
                    ];
                })->toArray(),
            ],
        ];
    }

    /**
     * Получить детальную информацию по использованию промокода
     */
    public function getPromoCodeUsageDetails($promoCodeId, $request)
    {
        $query = PromoCodeUsage::with([
            'client:id,email',
            'client.profile:id,client_id,first_name,last_name,phone',
            'order:id,order_number,status,payment_status,total_amount,total_amount_original,total_promo_discount,total_items_discount,created_at',
            'order.items:id,order_id,product_id,product_variant_id,quantity,price',
            'order.items.product:id,name',
            'order.items.variant:id,name',
        ])
            ->where('promo_code_usages.promo_code_id', $promoCodeId);

        // Фильтр по клиенту
        if ($request->filled('client_id')) {
            $query->where('promo_code_usages.client_id', $request->client_id);
        }

        if ($request->filled('client_email')) {
            $query->whereHas('client', function ($q) use ($request) {
                $q->where('email', $request->client_email);
            });
        }


        // Фильтр по статусу заказа
        if ($request->filled('order_status')) {
            $query->whereHas('order', function ($q) use ($request) {
                $q->where('status', $request->order_status);
            });
        }

        // Фильтр по статусу оплаты
        if ($request->filled('payment_status')) {
            $query->whereHas('order', function ($q) use ($request) {
                $q->where('payment_status', $request->payment_status);
            });
        }

        // Фильтр по дате использования
        if ($request->filled('date_from')) {
            $query->where('promo_code_usages.created_at', '>=', $request->date_from . ' 00:00:00');
        }

        if ($request->filled('date_to')) {
            $query->where('promo_code_usages.created_at', '<=', $request->date_to . ' 23:59:59');
        }

        // Клонируем запрос для подсчета общей суммы (до пагинации)
        $totalQuery = clone $query;

        // Подсчитываем общую сумму всех заказов с учетом фильтров
        $totalSum = $totalQuery->join('orders', 'promo_code_usages.order_id', '=', 'orders.id')
            ->sum('orders.total_amount');

        // Подсчитываем общую сумму скидок
        $totalDiscounts = $totalQuery->sum('promo_code_usages.discount_amount');

        // Сортировка
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');

        // Если сортировка по created_at, уточняем таблицу
        if ($sortBy == 'created_at') {
            $query->orderBy('promo_code_usages.created_at', $sortOrder);
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        // Пагинация
        $perPage = $request->get('per_page', 15);
        $usages = $query->paginate($perPage);

        // Преобразуем данные для удобного отображения
        $usages->getCollection()->transform(function ($usage) {
            return [
                'id' => $usage->id,
                'used_at' => $usage->created_at,
                'discount_amount' => $usage->discount_amount,
                'client' => [
                    'id' => $usage->client?->id,
                    'email' => $usage->client?->email,
                    'name' => $usage->client?->profile
                        ? $usage->client->profile->first_name . ' ' . $usage->client->profile->last_name
                        : 'N/A',
                    'phone' => $usage->client?->profile?->phone,
                ],
                'order' => [
                    'id' => $usage->order->id,
                    'number' => $usage->order->order_number,
                    'status' => $usage->order->status,
                    'payment_status' => $usage->order->payment_status,
                    'total_amount' => $usage->order->total_amount,
                    'total_amount_original' => $usage->order->total_amount_original,
                    'total_promo_discount' => $usage->order->total_promo_discount,
                    'total_items_discount' => $usage->order->total_items_discount,
                    'created_at' => $usage->order->created_at,
                    'items_count' => $usage->order->items->count(),
                    'items' => $usage->order->items->map(function ($item) {
                        return [
                            'product_name' => ($item->product->name ?? '') . (
                                $item?->variant?->name ? ' (' . $item?->variant->name . ')' : ''
                                ),
                            'quantity' => $item->quantity,
                            'price' => $item->price,
                            'subtotal' => $item->quantity * $item->price,
                        ];
                    }),
                ],
            ];
        });

        // Добавляем общую статистику к результату
        return [
            'data' => $usages->items(),
            'meta' => PaginationHelper::format($usages),
            'summary' => [
                'total_salse' => round($totalSum, 2),
                'total_discounts' => round($totalDiscounts, 2),
            ]
        ];
    }

    /**
     * Получить топ клиентов по использованию промокода
     */
    public function getTopClients($promoCodeId, $limit = 10)
    {
        return PromoCodeUsage::where('promo_code_usages.promo_code_id', $promoCodeId)
            ->join('clients', 'promo_code_usages.client_id', '=', 'clients.id')
            ->join('orders', 'promo_code_usages.order_id', '=', 'orders.id')
            ->select(
                'clients.id',
                'clients.name',
                'clients.email',
                DB::raw('COUNT(*) as usage_count'),
                DB::raw('SUM(promo_code_usages.discount_amount) as total_discount_received'),
                DB::raw('SUM(orders.total_amount) as total_spent')
            )
            ->groupBy('clients.id', 'clients.name', 'clients.email')
            ->orderBy('usage_count', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Получить статистику по периодам
     */
    public function getUsageByPeriod($promoCodeId, $period = 'daily', $dateFrom = null, $dateTo = null)
    {
        $dateFrom = $dateFrom ?? now()->subDays(30)->startOfDay();
        $dateTo = $dateTo ?? now()->endOfDay();

        $dateFormat = match ($period) {
            'hourly' => '%Y-%m-%d %H:00:00',
            'daily' => '%Y-%m-%d',
            'weekly' => '%Y-%u',
            'monthly' => '%Y-%m',
            default => '%Y-%m-%d',
        };

        return PromoCodeUsage::where('promo_code_usages.promo_code_id', $promoCodeId)
            ->whereBetween('promo_code_usages.created_at', [$dateFrom, $dateTo])
            ->join('orders', 'promo_code_usages.order_id', '=', 'orders.id')
            ->select(
                DB::raw("DATE_FORMAT(promo_code_usages.created_at, '{$dateFormat}') as period"),
                DB::raw('COUNT(*) as usage_count'),
                DB::raw('COUNT(DISTINCT promo_code_usages.client_id) as unique_clients'),
                DB::raw('SUM(promo_code_usages.discount_amount) as total_discount'),
                DB::raw('SUM(orders.total_amount) as total_sales')
            )
            ->groupBy('period')
            ->orderBy('period', 'asc')
            ->get();
    }

}
