<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use DB;
use Illuminate\Http\Request;

class CartAnalyticsController extends Controller
{
    public function cartAnalytics(Request $request)
    {
        $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        $cartsQuery = Cart::query()
            ->when($request->filled('date_from'), fn($q) => $q->whereDate('created_at', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn($q) => $q->whereDate('created_at', '<=', $request->date_to));

        $allCarts = (clone $cartsQuery)->get();
        $abandonedCarts = $allCarts->where('status', 'abandoned');
        $orderedCarts = $allCarts->where('status', 'ordered');

        $totalCarts = $allCarts->count();
        $totalAbandoned = $abandonedCarts->count();
        $totalOrdered = $orderedCarts->count();

        $lostRevenue = $abandonedCarts->sum('total');
        // $lostRevenueOriginal = $abandonedCarts->sum('total_original');
        $lostDiscount = $abandonedCarts->sum('total_discount');

        $totalRevenue = $orderedCarts->sum('total');
        $totalDiscount = $orderedCarts->sum('total_discount');
        $avgOrderValue = $totalOrdered ? round($totalRevenue / $totalOrdered, 2) : 0;
        $avgDiscount = $totalOrdered ? round($totalDiscount / $totalOrdered, 2) : 0;

        $totalItems = CartItem
            ::join('carts', 'cart_items.cart_id', '=', 'carts.id')
            ->when($request->filled('date_from'), fn($q) => $q->whereDate('carts.created_at', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn($q) => $q->whereDate('carts.created_at', '<=', $request->date_to))
            ->sum('cart_items.quantity');

        $topProducts = CartItem
            ::join('products', 'cart_items.product_id', '=', 'products.id')
            ->join('carts', 'cart_items.cart_id', '=', 'carts.id')
            ->when($request->filled('date_from'), fn($q) => $q->whereDate('carts.created_at', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn($q) => $q->whereDate('carts.created_at', '<=', $request->date_to))
            ->select('products.name', DB::raw('SUM(cart_items.quantity) as total_quantity'))
            ->groupBy('cart_items.product_id', 'products.name')
            ->orderByDesc('total_quantity')
            ->limit(5)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'total_carts' => $totalCarts, // всего корзин
                'abandoned_carts' => $totalAbandoned, // всего брошенных корзин
                'ordered_carts' => $totalOrdered, // всего заказанных корзин
                'total_revenue' => $totalRevenue, // всего дохода от заказов
                'total_discount' => $totalDiscount, // всего скидок от заказов
                'lost_revenue' => $lostRevenue, // упущенный доход
                // 'lost_revenue_original' => $lostRevenueOriginal,
                'lost_discount' => $lostDiscount,// упущенные скидки
                'average_order_value' => $avgOrderValue, // cр.стоимость корзины
                'average_discount' => $avgDiscount, // cр.скидка
                'total_items_qty' => $totalItems, // всего товаров заказано
                'top_products' => $topProducts,
            ]
        ]);
    }
}
