<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Client;
use App\Models\Discount;
use App\Models\Order;
use App\Models\OrderDiscount;
use App\Models\OrderItem;
use App\Models\PromoCode;
use Illuminate\Http\Request;

class DiscountAnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $analytics = [];

        if ($request->boolean('get_discounts', false)) {
            $discounts = OrderDiscount
                ::select('discount_id')
                ->selectRaw('COUNT(*) as usage_count')
                ->selectRaw('SUM(discount_amount) as total_discount')
                ->selectRaw('SUM(original_price) as original_sum')
                ->groupBy('discount_id')
                ->get()
                ->map(function ($row) {
                    $discount = Discount::find($row->discount_id);
                    return [
                        'discount_id' => $row->discount_id,
                        'discount_name' => $discount?->name ?? '—',
                        'discount_type' => $discount?->discount_type ?? 'unknown',
                        'usage_count' => $row->usage_count,
                        'total_discount' => $row->total_discount,
                        'original_price_sum' => $row->original_sum,
                    ];
                });

            $analytics['discounts'] = $discounts;
        }

        if ($request->boolean('get_coupons', false)) {
            $coupons = Order
                ::select('promo_code_id')
                ->whereNotNull('promo_code_id')
                ->selectRaw('COUNT(*) as usage_count')
                ->selectRaw('SUM(discount_amount) as total_discount')
                ->selectRaw('SUM(total_amount) as total_order_value')
                ->groupBy('promo_code_id')
                ->get()
                ->map(function ($row) {
                    $promo = PromoCode::find($row->promo_code_id);
                    return [
                        'promo_code_id' => $row->promo_code_id,
                        'code' => $promo?->code ?? '—',
                        'usage_count' => $row->usage_count,
                        'total_discount' => $row->total_discount,
                        'total_order_value' => $row->total_order_value,
                    ];
                });

            $analytics['coupons'] = $coupons;
        }

        if ($request->boolean('get_clients', false)) {
            $clients = Order
                ::whereNotNull('discount_amount')
                ->where('discount_amount', '>', 0)
                ->select('client_id')
                ->selectRaw('COUNT(*) as orders_with_discounts')
                ->selectRaw('SUM(discount_amount) as total_discount')
                ->selectRaw('SUM(total_amount) as total_spent')
                ->groupBy('client_id')
                ->get()
                ->map(function ($row) {
                    $client = Client::find($row->client_id);
                    return [
                        'client_id' => $row->client_id,
                        'name' => $client?->get_full_name() ?? '—',
                        'email' => $client?->email ?? null,
                        'orders_with_discounts' => $row->orders_with_discounts,
                        'total_discount' => $row->total_discount,
                        'total_spent' => $row->total_spent,
                    ];
                });

            $analytics['clients'] = $clients;
        }

        if ($request->boolean('get_categories', false)) {
            $categories = OrderItem
                ::join('products', 'order_items.product_id', '=', 'products.id')
                ->join('category_product', 'products.id', '=', 'category_product.product_id')
                ->join('order_discounts', function ($join) {
                    $join->on('order_discounts.order_id', '=', 'order_items.order_id')
                        ->on('order_discounts.discountable_id', '=', 'order_items.product_id')
                        ->where('order_discounts.discountable_type', '=', 'product');
                })
                ->select('category_product.category_id')
                ->selectRaw('COUNT(order_items.id) as total_items')
                ->selectRaw('SUM(order_discounts.discount_amount) as total_discount')
                ->groupBy('category_product.category_id')
                ->get()
                ->map(function ($row) {
                    $category = Category::find($row->category_id);
                    return [
                        'category_id' => $row->category_id,
                        'name' => $category?->name ?? '—',
                        'total_items_discounted' => $row->total_items,
                        'total_discount' => $row->total_discount,
                    ];
                });

            $analytics['categories'] = $categories;
        }

        return response()->json([
            'success' => true,
            'data' => $analytics
        ]);
    }
}
