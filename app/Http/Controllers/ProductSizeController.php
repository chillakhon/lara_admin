<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductSize;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProductSizeController extends Controller
{
    public function store(Request $request, Product $product)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $size = $product->sizes()->create($validator->validated());

        return back()->with('success', 'Size added successfully.');
    }

    public function destroy(Product $product, ProductSize $size)
    {
        $size->delete();

        return back()->with('success', 'Size removed successfully.');
    }
}
