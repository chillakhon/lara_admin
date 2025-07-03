<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\DiscountRequest;
use App\Http\Resources\DiscountResource;
use App\Models\Category;
use App\Models\CategoryProduct;
use App\Models\Discount;
use App\Models\Product;
use App\Models\ProductVariant;
use DB;
use Illuminate\Http\Request;

class DiscountController extends Controller
{


    public function index(Request $request)
    {
        $perPage = $request->get('per_page', $request->get('per_page') ?? 15);

        $discounts = Discount
            ::with(['categories', 'products'])
            ->orderBy('priority');

        if ($request->get('name')) {
            $discounts->where('name', 'like', "%{$request->get("name")}%");
        }

        if ($request->get('type')) {
            $discounts->where('type', $request->get('type'));
        }

        if ($request->get('discount_type')) {
            $discounts->where('discount_type', $request->get('discount_type'));
        }

        $discounts = $discounts->paginate($perPage);

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
                $this->reassignProductsToDiscount($request->products, $discount);

                if ($request->has('product_variants') && count($request->get('product_variants')) >= 1) {
                    $this->reassignVariantsToDiscount($request->product_variants, $discount);
                } else {
                    $productVariantIds = ProductVariant
                        ::whereNull('deleted_at')
                        ->whereIn('product_id', $request->products)
                        ->pluck('id')
                        ->toArray();

                    $this->reassignVariantsToDiscount($productVariantIds, $discount);
                }
            }
        } elseif ($request->discount_type === 'category') {
            if ($request->has('categories')) {
                $discount->categories()->attach($request->categories);

                $productIds = CategoryProduct
                    ::whereIn('category_id', $request->get('categories'))
                    ->pluck('product_id')->unique()->toArray();

                $variantIds = ProductVariant
                    ::whereIn('product_id', $productIds)
                    ->pluck('id')
                    ->toArray();

                $this->reassignProductsToDiscount($productIds, $discount);
                $this->reassignVariantsToDiscount($variantIds, $discount);
            }

        } elseif ($request->discount_type === 'all') {
            $allProductIds = Product::pluck('id')->toArray();
            $allVariantIds = ProductVariant::pluck('id')->toArray();

            $this->reassignProductsToDiscount($allProductIds, $discount);
            $this->reassignVariantsToDiscount($allVariantIds, $discount);

            if (!empty($allProductIds) || !empty($allVariantIds)) {
                Discount::whereNot('id', $discount->id)->update([
                    'is_active' => 0,
                ]);
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
            if ($request->has('products')) {
                $this->reassignProductsToDiscount($request->products, $discount);

                if ($request->has('product_variants') && count($request->get('product_variants')) >= 1) {
                    $this->reassignVariantsToDiscount($request->product_variants, $discount);
                } else {
                    $productVariantIds = ProductVariant
                        ::whereNull('deleted_at')
                        ->whereIn('product_id', $request->products)
                        ->pluck('id')
                        ->toArray();

                    $this->reassignVariantsToDiscount($productVariantIds, $discount);
                }
            }


        } elseif ($request->discount_type === 'category') {
            if ($request->has('categories')) {
                $discount->categories()->attach($request->categories);


                $productIds = CategoryProduct
                    ::whereIn('category_id', $request->get('categories'))
                    ->pluck('product_id')->unique()->toArray();

                $variantIds = ProductVariant
                    ::whereIn('product_id', $productIds)
                    ->pluck('id')
                    ->toArray();

                $this->reassignProductsToDiscount($productIds, $discount);
                $this->reassignVariantsToDiscount($variantIds, $discount);
            }

        } elseif ($request->discount_type === 'all') {
            $allProductIds = Product::pluck('id')->toArray();
            $allVariantIds = ProductVariant::pluck('id')->toArray();

            $this->reassignProductsToDiscount($allProductIds, $discount);
            $this->reassignVariantsToDiscount($allVariantIds, $discount);
        }

        return response()->json([
            'message' => 'Скидка успешно обновлена',
            'discount' => new DiscountResource($discount),
        ]);
    }

    private function reassignProductsToDiscount(array $productIds, Discount $discount): void
    {
        Discount::whereHas('products', function ($query) use ($productIds) {
            $query->whereIn('products.id', $productIds);
        })->get()->each(function ($oldDiscount) use ($productIds) {
            $oldDiscount->products()->detach($productIds);
        });

        $discount->products()->attach($productIds);
    }

    private function reassignVariantsToDiscount(array $variantIds, Discount $discount): void
    {
        Discount::whereHas('productVariants', function ($query) use ($variantIds) {
            $query->whereIn('product_variants.id', $variantIds);
        })->get()->each(function ($oldDiscount) use ($variantIds) {
            $oldDiscount->productVariants()->detach($variantIds);
        });

        $discount->productVariants()->attach($variantIds);
    }

    public function destroy(Discount $discount)
    {
        $discount->delete();

        return response()->json([
            'message' => 'Скидка успешно удалена',
        ]);
    }
}

