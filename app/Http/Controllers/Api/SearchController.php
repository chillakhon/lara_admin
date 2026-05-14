<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
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
                ->where('name', 'like', "%{$query}%")
                ->select(['id', 'name'])
                ->take(10)
                ->get(),

            'variants' => $this->searchVariants($request),

            'orders' => Order::where('order_number', 'like', "%{$query}%")
                ->select(['id', 'order_number as name'])
                ->take(10)
                ->get(),

            'categories' => Category::where('name', 'like', "%{$query}%")
                ->select(['id', 'name'])
                ->take(10)
                ->get(),

            'clients' => \App\Models\Client::query()
                ->join('users', 'clients.user_id', '=', 'users.id')
                ->whereNull('clients.deleted_at')
                ->where(function ($q) use ($query) {
                    $q->where('clients.first_name', 'like', "%{$query}%")
                        ->orWhere('clients.last_name', 'like', "%{$query}%");
                })
                ->select([
                    'clients.id',
                    \DB::raw("CONCAT(clients.first_name, ' ', clients.last_name) as name")
                ])
                ->where('users.type', 'client')
                ->take(10)
                ->get(),

            default => []
        };
    }

    private function searchVariants(Request $request)
    {
        $query = $request->input('query');
        $query = ProductVariant::where('name', 'like', "%{$query}%");
        
        if ($request->has('product_ids')) {
            $query->whereIn('product_id', $request->input('product_ids'));
        }
        
        return $query->select(['id', 'name', 'product_id'])
            ->with('product:id,name')
            ->take(10)
            ->get();
    }
}
