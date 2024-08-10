<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductComponentRequest;
use App\Http\Requests\UpdateProductComponentRequest;
use App\Models\Product;
use App\Models\ProductComponent;
use App\Models\ProductSize;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProductComponentController extends Controller
{
    public function store(Request $request, Product $product, ProductSize $size)
    {
        $validator = Validator::make($request->all(), [
            'material_id' => 'required|exists:materials,id',
            'quantity' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $component = $size->components()->create([
            'product_id' => $product->id,
            'material_id' => $request->material_id,
            'quantity' => $request->quantity,
        ]);

        return back()->with('success', 'Component added successfully.');
    }

    public function destroy(Product $product, ProductSize $size, ProductComponent $component)
    {
        $component->delete();

        return back()->with('success', 'Component removed successfully.');
    }
}
