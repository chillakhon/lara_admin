<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::query()
            ->with([
                'categories',
                'variants',
                'sizes',
                'images'
            ])
            ->where('is_available', true);

        if ($request->has('category_id')) {
            $query->whereHas('categories', function ($q) use ($request) {
                $q->where('categories.id', $request->category_id);
            });
        }

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $products = $query->paginate(20);

        return ProductResource::collection($products);
    }

    public function show($id)
    {
        $product = Product::with([
            'categories',
            'variants.size',
            'variants.images',
        ])
            ->findOrFail($id);

        return new ProductResource($product);
    }

    public function showBySlug($slug)
    {
        $product = Product::where('slug', $slug)
            ->with([
                'categories',
                'variants.size',
                'variants.images',
            ])
            ->firstOrFail();

        return new ProductResource($product);
    }
}
