<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Color;
use App\Models\ColorCategory;
use App\Models\ColorOption;
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
            'categories' => Category::all(),
        ]);
    }

    public function show(Product $product)
    {
        $product->load(['sizes.components.material', 'variants', 'colorOptions.category', 'colorOptions.colorOptionValues.color', 'images' => function ($query) {$query->with('imagable');}] );
        $materials = Material::all();
        $categories = Category::with('colorOptions')->get();
        $colors = Color::with('images')->get();

        return Inertia::render('Dashboard/Products/Show', [
            'product' => $product,
            'materials' => $materials,
            'categories' => $categories,
            'colors' => $colors,
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
        ]);

        $product->update($validated);
        $product->categories()->sync($validated['categories']);


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

    public function addColorOption(Request $request, Product $product)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
        ]);

        $colorOption = $product->colorOptions()->create($validated);

        return redirect()->back()->with('success', 'Color option added successfully.');
    }

    public function removeColorOption(Product $product, ColorOption $colorOption)
    {
        $colorOption->delete();

        return redirect()->back()->with('success', 'Color option removed successfully.');
    }

    public function addColorToOption(Request $request, Product $product, ColorOption $colorOption)
    {
        $validated = $request->validate([
            'color_id' => 'required|exists:colors,id',
        ]);

        $colorOption->colorOptionValues()->create([
            'color_id' => $validated['color_id'],
            'color_option_id' => $colorOption->id,
            'product_id' => $product->id
        ]);

        return redirect()->back()->with('success', 'Color added to option successfully.');
    }

    public function removeColorFromOption(Product $product, ColorOption $colorOption, $colorOptionValueId)
    {
        $colorOption->colorOptionValues()->findOrFail($colorOptionValueId)->delete();

        return redirect()->back()->with('success', 'Color removed from option successfully.');
    }
}
