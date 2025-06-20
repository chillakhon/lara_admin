<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
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
            'parent_id' => 'nullable|exists:categories,id'
        ]);

        $category = new Category();
        $category->name = $validated['name'];
        $category->description = $validated['description'] ?? null;
        // slug will be automatically generated

        if ($validated['parent_id']) {
            $parent = Category::findOrFail($validated['parent_id']);
            $category->appendToNode($parent)->save();
        } else {
            $category->save();
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
            'parent_id' => 'nullable|exists:categories,id'
        ]);

        $category->name = $validated['name'];
        // slug will be automatically updated if name changes

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

        return response()->json(['message' => 'Category updated successfully', 'category' => $category]);
    }

    public function destroy(Category $category)
    {
        $category->delete();
        return response()->json(['message' => 'Category deleted successfully']);
    }
}
