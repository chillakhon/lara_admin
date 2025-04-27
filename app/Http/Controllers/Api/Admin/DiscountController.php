<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\DiscountRequest;
use App\Http\Resources\DiscountResource;
use App\Models\Discount;
use Illuminate\Http\Request;

class DiscountController extends Controller
{


    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 10);
        $discounts = Discount::with(['products', 'productVariants'])
            ->orderBy('priority')
            ->paginate($perPage);

        return response()->json([
            'data' => DiscountResource::collection($discounts),
            'meta' => [
                'current_page' => $discounts->currentPage(),
                'per_page' => $discounts->perPage(),
                'total' => $discounts->total(),
                'last_page' => $discounts->lastPage(),
            ],
        ]);
    }

//    public function index(Request $request)
//    {
//        $perPage = $request->get('per_page', 10);
//        $discounts = Discount::with(['products', 'productVariants'])
//            ->orderBy('priority')
//            ->simplePaginate($perPage);
//
//        return response()->json($discounts);
//    }

    public function store(DiscountRequest $request)
    {
        $discount = Discount::create($request->validated());

        if ($request->discount_type === 'specific') {
            if ($request->has('products')) {
                $discount->products()->attach($request->products);
            }
            if ($request->has('product_variants')) {
                $discount->productVariants()->attach($request->product_variants);
            }
        } elseif ($request->discount_type === 'category') {
            if ($request->has('categories')) {
                $discount->categories()->attach($request->categories);
            }
        }

        return response()->json([
            'message' => 'Скидка успешно создана',
            'discount' => new DiscountResource($discount),
        ], 201);
    }

    public function update(DiscountRequest $request, Discount $discount)
    {
        $discount->update($request->validated());

        $discount->categories()->sync([]);
        $discount->products()->sync([]);
        $discount->productVariants()->sync([]);

        if ($request->discount_type === 'specific') {
            $discount->products()->sync($request->products ?? []);
            $discount->productVariants()->sync($request->product_variants ?? []);
        } elseif ($request->discount_type === 'category') {
            $discount->categories()->sync($request->categories ?? []);
        }

        return response()->json([
            'message' => 'Скидка успешно обновлена',
            'discount' => new DiscountResource($discount),
        ]);
    }

    public function destroy(Discount $discount)
    {
        $discount->delete();

        return response()->json([
            'message' => 'Скидка успешно удалена',
        ]);
    }
}

