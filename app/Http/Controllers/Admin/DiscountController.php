<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Discount;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Category;
use App\Http\Requests\DiscountRequest;
use App\Http\Resources\DiscountResource;
use Inertia\Inertia;

class DiscountController extends Controller
{
    public function index()
    {
        $discounts = Discount::with(['products', 'productVariants', 'categories'])
            ->orderBy('priority')
            ->paginate(15);

        return Inertia::render('Dashboard/Discounts/Index', [
            'discounts' => DiscountResource::collection($discounts),
            'products' => Product::select('id', 'name')->get(),
            'productVariants' => ProductVariant::select('id', 'name')->get(),
            'categories' => Category::select('id', 'name')->get()
        ]);
    }

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

        return redirect()->back()->with('success', 'Скидка успешно создана');
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

        return redirect()->back()->with('success', 'Скидка успешно обновлена');
    }

    public function destroy(Discount $discount)
    {
        $discount->delete();
        return redirect()->back()->with('success', 'Скидка успешно удалена');
    }
} 