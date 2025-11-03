<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class SimpleProductController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = Product::query();

            // Фильтр по ID продукта
            if ($request->filled('product_id')) {
                $query->where('id', $request->product_id);
            }

            // Поиск по названию
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where('name', 'like', "%{$search}%");
            }

            // Фильтр по категории
            if ($request->filled('category_id')) {
                $query->where('category_id', $request->category_id);
            }

            if ($request->boolean('withVariants')) {
                $query->with(['variants']);
            }

            $perPage = $request->get('per_page', 10);
            $products = $query->paginate($perPage);
            return response()->json([
                'success' => true,
                'data' => $products->items(),
                'meta' => [
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                    'per_page' => $products->perPage(),
                    'total' => $products->total(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
            ], 500);
        }
    }
}
