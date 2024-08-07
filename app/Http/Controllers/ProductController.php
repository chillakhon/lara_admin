<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\MaterialService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProductController extends Controller
{
    protected $materialService;

    public function __construct(MaterialService $materialService)
    {
        $this->materialService = $materialService;
    }

    public function index()
    {
        $products = Product::with('components.material')->paginate(10);
        return Inertia::render('Dashboard/Products/Index', [
            'products' => $products
        ]);
    }

    public function show(Product $product)
    {
        $product->load('components.material', 'variants');
        $baseCost = $this->materialService->calculateProductCost($product);

        return Inertia::render('Dashboard/Products/Show', [
            'product' => $product,
            'baseCost' => $baseCost,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $product = Product::create($validated);

        return redirect()->back()->with('success', 'Product created successfully.');
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $product->update($validated);

        return redirect()->back()->with('success', 'Product updated successfully.');
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return redirect()->back()->with('success', 'Product deleted successfully.');
    }

    public function addComponent(Request $request, Product $product)
    {
        $validated = $request->validate([
            'material_id' => 'required|exists:materials,id',
            'quantity' => 'required|numeric|min:0',
        ]);

        $product->components()->create($validated);

        return redirect()->back()->with('success', 'Component added successfully.');
    }

    public function removeComponent(Product $product, $componentId)
    {
        $product->components()->findOrFail($componentId)->delete();

        return redirect()->back()->with('success', 'Component removed successfully.');
    }
    public function calculateCost(Product $product)
    {
        $cost = $this->materialService->calculateProductCost($product);

        return redirect()->back()->with('info', "The calculated cost is: $cost");
    }

    public function createVariant(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'additional_cost' => 'required|numeric|min:0',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
        ]);

        $variant = $product->variants()->create($validated);

        return redirect()->back()->with('success', 'Variant created successfully.');
    }

    public function updateVariant(Request $request, ProductVariant $variant)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'additional_cost' => 'required|numeric|min:0',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
        ]);

        $variant->update($validated);

        return redirect()->back()->with('success', 'Variant updated successfully.');
    }

    public function deleteVariant(ProductVariant $variant)
    {
        $variant->delete();

        return redirect()->back()->with('success', 'Variant deleted successfully.');
    }
}
