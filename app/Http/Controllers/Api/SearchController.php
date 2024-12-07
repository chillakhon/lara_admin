<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function search(Request $request)
    {
        $query = $request->input('query');
        $type = $request->input('type');

        return match ($type) {
            'products' => Product::query()
                ->where('is_active', true)
                ->where(function ($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                        ->orWhere('slug', 'like', "%{$query}%");
                })
                ->with(['defaultUnit', 'variants' => function ($q) {
                    $q->where('is_active', true)
                        ->select(['id', 'product_id', 'name', 'sku', 'price']);
                }])
                ->select([
                    'id',
                    'name',
                    'slug',
                    'type',
                    'default_unit_id',
                    'has_variants',
                    'allow_preorder',
                    'after_purchase_processing_time'
                ])
                ->take(10)
                ->get()
                ->map(function ($product) {
                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'type' => $product->type,
                        'unit' => $product->defaultUnit?->name,
                        'has_variants' => $product->has_variants,
                        'variants' => $product->when($product->has_variants, function () use ($product) {
                            return $product->variants->map(function ($variant) {
                                return [
                                    'id' => $variant->id,
                                    'name' => $variant->name,
                                    'sku' => $variant->sku,
                                    'price' => $variant->price,
                                ];
                            });
                        }),
                        'allow_preorder' => $product->allow_preorder,
                        'processing_time' => $product->after_purchase_processing_time,
                    ];
                }),

            'orders' => Order::where('order_number', 'like', "%{$query}%")
                ->with(['client:id,first_name,last_name,email'])
                ->select(['id', 'order_number', 'client_id', 'status', 'total_amount'])
                ->take(10)
                ->get()
                ->map(function ($order) {
                    return [
                        'id' => $order->id,
                        'order_number' => $order->order_number,
                        'client' => $order->client ? [
                            'id' => $order->client->id,
                            'name' => $order->client->first_name . ' ' . $order->client->last_name,
                            'email' => $order->client->email,
                        ] : null,
                        'status' => $order->status,
                        'total_amount' => $order->total_amount,
                    ];
                }),

            'categories' => Category::where('name', 'like', "%{$query}%")
                ->select(['id', 'name', 'slug'])
                ->take(10)
                ->get()
                ->map(function ($category) {
                    return [
                        'id' => $category->id,
                        'name' => $category->name,
                        'slug' => $category->slug,
                    ];
                }),

            'clients' => \App\Models\Client::query()
                ->join('users', 'clients.user_id', '=', 'users.id')
                ->where(function ($q) use ($query) {
                    $q->where('clients.first_name', 'like', "%{$query}%")
                        ->orWhere('clients.last_name', 'like', "%{$query}%")
                        ->orWhere('clients.phone', 'like', "%{$query}%")
                        ->orWhere('users.email', 'like', "%{$query}%");
                })
                ->select([
                    'clients.id',
                    'clients.first_name',
                    'clients.last_name',
                    'clients.phone',
                    'clients.bonus_balance',
                    'users.email'
                ])
                ->where('users.type', 'client')
                ->take(10)
                ->get()
                ->map(function ($client) {
                    return [
                        'id' => $client->id,
                        'name' => "{$client->first_name} {$client->last_name}",
                        'email' => $client->email,
                        'phone' => $client->phone,
                        'bonus_balance' => $client->bonus_balance
                    ];
                }),

            default => []
        };
    }
}
