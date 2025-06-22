<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductNumberTwoResouce;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        // Получаем только корневые категории с их потомками
        $categories = Category::with('children');

        if ($request->get('id')) {
            $categories->where('id', $request->get('id'));
        }

        if ($request->get('name')) {
            $categories->where('name', 'like', "%{$request->get('name')}%");
        }

        if ($request->get('slug')) {
            $categories->where('slug', $request->get('slug'));
        }

        if ($request->boolean('get_children', false)) {
            $categories->whereNotNull('parent_id');
        } else {
            $categories->whereIsRoot();
        }

        $categories = $categories
            ->defaultOrder()
            ->get();

        return CategoryResource::collection($categories);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:categories,id',
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'exists:products,id',
        ]);

        $category = new Category();
        $category->name = $validated['name'];
        $category->description = $validated['description'] ?? null;

        if ($validated['parent_id']) {
            $parent = Category::findOrFail($validated['parent_id']);
            $category->appendToNode($parent)->save();
        } else {
            $category->save();
        }

        if (!empty($validated['product_ids'])) {
            $category->products()->attach($validated['product_ids']);
        }

        return response()->json([
            'message' => 'Category created successfully.',
            'category' => $category
        ], 201);
    }

    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:categories,id',
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'exists:products,id',
        ]);

        $category->name = $validated['name'];
        $category->description = $validated['description'] ?? null;

        if ($validated['parent_id'] !== $category->parent_id) {
            if ($validated['parent_id']) {
                $parent = Category::findOrFail($validated['parent_id']);
                $category->appendToNode($parent)->save();
            } else {
                $category->makeRoot()->save();
            }
        } else {
            $category->save();
        }

        if (array_key_exists('product_ids', $validated)) {
            $category->products()->sync($validated['product_ids']);
        }

        return response()->json([
            'message' => 'Category updated successfully',
            'category' => $category
        ]);
    }

    public function destroy(Category $category)
    {
        $category->products()->detach();
        $category->delete();
        return response()->json(['message' => 'Категория удалена!']);
    }

    public function get_products_of_category(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
        ]);

        $category = Category::with('products')->findOrFail($validated['category_id']);

        return response()->json([
            'category_id' => $category->id,
            'category_name' => $category->name,
            'products' => ProductNumberTwoResouce::collection($category->products),
        ]);
    }
}
