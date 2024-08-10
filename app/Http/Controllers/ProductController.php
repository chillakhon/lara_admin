<?php

namespace App\Http\Controllers;

use App\Models\Category;
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
        return Inertia::render('Dashboard/Products/Index', [
            'products' => Product::with('categories')->paginate(10),
            'categories' => Category::with('options.values')->get(),
        ]);
    }

    public function show(Product $product)
    {
        $product->load('sizes.components.material', 'variants');
        $materials = Material::all();

        return Inertia::render('Dashboard/Products/Show', [
            'product' => $product,
            'materials' => $materials,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_available' => 'boolean',
            'categories' => 'required|array',
            'categories.*' => 'exists:categories,id',
        ]);

        $product = Product::create($validated);
        $product->categories()->sync($validated['categories']);

//        foreach ($validated['variants'] as $variantData) {
//            $variant = $product->variants()->create($variantData);
//            $variant->optionValues()->sync($variantData['option_values']);
//        }

        return redirect()->back()->with('success', 'Продукт успешно создан.');
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_available' => 'boolean',
            'categories' => 'required|array',
            'categories.*' => 'exists:categories,id',
            'variants' => 'required|array',
            'variants.*.id' => 'sometimes|exists:product_variants,id',
            'variants.*.name' => 'required|string',
            'variants.*.article' => 'required|string',
            'variants.*.additional_cost' => 'required|numeric',
            'variants.*.price' => 'required|numeric',
            'variants.*.stock' => 'required|integer',
            'variants.*.option_values' => 'required|array',
        ]);

        $product->update($validated);
        $product->categories()->sync($validated['categories']);

        foreach ($validated['variants'] as $variantData) {
            if (isset($variantData['id'])) {
                $variant = $product->variants()->findOrFail($variantData['id']);
                $variant->update($variantData);
            } else {
                $variant = $product->variants()->create($variantData);
            }
            $variant->optionValues()->sync($variantData['option_values']);
        }

        return redirect()->back()->with('success', 'Продукт успешно обновлен.');
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
