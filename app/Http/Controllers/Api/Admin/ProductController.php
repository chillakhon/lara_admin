<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Image;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Unit;
use App\Services\MaterialService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    protected $materialService;

    public function __construct(MaterialService $materialService)
    {
        $this->materialService = $materialService;
    }

    public function index(Request $request)
    {
        $products = Product::with(['categories', 'options', 'variants'])
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('categories', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            })
            ->when($request->category, function ($query, $categoryId) {
                $query->whereHas('categories', function ($q) use ($categoryId) {
                    $q->where('categories.id', $categoryId);
                });
            })
            ->latest()
            ->paginate(10);

        return response()->json($products);
    }

    public function show(Product $product)
    {
        $product->load(['categories', 'options.values', 'variants.optionValues.option', 'variants.images', 'variants.unit', 'defaultUnit']);
        return response()->json($product);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:simple,manufactured,composite',
            'default_unit_id' => 'nullable|exists:units,id',
            'is_active' => 'boolean',
            'has_variants' => 'boolean',
            'allow_preorder' => 'boolean',
            'after_purchase_processing_time' => 'integer|min:0',
            'categories' => 'required|array',
            'categories.*' => 'exists:categories,id',
        ]);

        $product = Product::create(array_merge($validated, ['slug' => Str::slug($validated['name'])]));
        $product->categories()->sync($validated['categories']);

        return response()->json([
            'message' => 'Product created successfully',
            'product' => $product], 201);
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:simple,manufactured,composite',
            'default_unit_id' => 'nullable|exists:units,id',
            'is_active' => 'boolean',
            'has_variants' => 'boolean',
            'allow_preorder' => 'boolean',
            'after_purchase_processing_time' => 'integer|min:0',
            'categories' => 'required|array',
            'categories.*' => 'exists:categories,id',
        ]);

        $product->update($validated);
        $product->categories()->sync($validated['categories']);

        return response()->json(['message' => 'Product updated successfully', 'product' => $product]);
    }

    public function destroy(Product $product)
    {
        $product->delete();
        return response()->json(['message' => 'Product deleted successfully']);
    }

    public function storeImages(Request $request, Product $product)
    {
        $request->validate([
            'images' => 'required|array',
            'images.*' => 'required|image|max:5120',
        ]);

        $uploadedImages = [];
        foreach ($request->file('images') as $image) {
            $path = $image->store('products', 'public');
            $imageModel = $product->images()->create(['path' => $path, 'url' => Storage::url($path)]);
            $uploadedImages[] = $imageModel;
        }

        return response()->json(['message' => 'Images uploaded successfully', 'images' => $uploadedImages]);
    }
}
