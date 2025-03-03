<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Client;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function getAnalytics(): JsonResponse
    {
        $now = Carbon::now();
        $thirtyDaysAgo = $now->copy()->subDays(30);
        
        // Основные метрики
        $metrics = [
            'total_revenue' => Order::whereBetween('created_at', [$thirtyDaysAgo, $now])
                ->where('status', '!=', 'cancelled')
                ->sum('total_amount'),
                
            'orders_count' => Order::whereBetween('created_at', [$thirtyDaysAgo, $now])
                ->count(),
                
            'average_order' => Order::whereBetween('created_at', [$thirtyDaysAgo, $now])
                ->where('status', '!=', 'cancelled')
                ->avg('total_amount') ?? 0,
                
            'new_clients' => Client::whereBetween('created_at', [$thirtyDaysAgo, $now])
                ->count(),
        ];

        // Данные для графика продаж по дням
        $salesChart = Order::whereBetween('created_at', [$thirtyDaysAgo, $now])
            ->where('status', '!=', 'cancelled')
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(total_amount) as total'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Статусы заказов
        $orderStatuses = Order::whereBetween('created_at', [$thirtyDaysAgo, $now])
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get();

        // Топ продаваемых товаров
        $topProducts = DB::table('order_items')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->select(
                'products.name',
                DB::raw('SUM(order_items.quantity) as total_quantity'),
                DB::raw('SUM(order_items.price * order_items.quantity) as total_revenue')
            )
            ->whereBetween('order_items.created_at', [$thirtyDaysAgo, $now])
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_quantity')
            ->limit(5)
            ->get();

        // Источники заказов
        $orderSources = Order::whereBetween('created_at', [$thirtyDaysAgo, $now])
            ->select('source', DB::raw('COUNT(*) as count'))
            ->whereNotNull('source')
            ->groupBy('source')
            ->orderByDesc('count')
            ->get();

        return response()->json([
            'metrics' => $metrics,
            'sales_chart' => $salesChart,
            'order_statuses' => $orderStatuses,
            'top_products' => $topProducts,
            'order_sources' => $orderSources
        ]);
    }
}
