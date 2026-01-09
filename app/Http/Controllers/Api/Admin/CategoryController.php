<?php

namespace App\Http\Controllers\Api\Admin;

use App\Helpers\PaginationHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductNumberTwoResouce;
use App\Models\Category;
use App\Models\CategoryProduct;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CategoryController extends Controller
{
    public function index(Request $request)
    {

        $perPage = (int)$request->get('per_page', 15);

        // Получаем только корневые категории с их потомками
        $categories = Category::with('children');

        if ($request->get('id')) {
            $categories->where('id', $request->get('id'));
        }

        if ($request->get('search')) {
            $categories
                ->where('name', 'like', "%{$request->get('search')}%")
                ->orWhere('slug', 'like', "%{$request->get('search')}%");
        }


        if ($request->boolean('get_children', false)) {
            $categories->whereNotNull('parent_id');
        } else {
            $categories->whereIsRoot();
        }

        $categories = $categories
            ->defaultOrder()
            ->paginate($perPage);

        return response()->json([
            'data' => CategoryResource::collection($categories->items()),
            'meta' => PaginationHelper::format($categories),
        ]);

    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:categories,id',
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'exists:products,id',

            'show_in_catalog_menu' => 'nullable|boolean',
            'show_as_home_banner' => 'nullable|boolean',
            'is_new_product' => 'nullable|boolean',
            'menu_order' => 'nullable|integer|min:0',
            'banner_image' => 'nullable|image|max:10240',
        ]);

        $category = new Category();
        $category->name = $validated['name'];
        $category->description = $validated['description'] ?? null;
        $category->show_in_catalog_menu = $validated['show_in_catalog_menu'] ?? false;
        $category->show_as_home_banner = $validated['show_as_home_banner'] ?? false;
        $category->is_new_product = $validated['is_new_product'] ?? false;
        $category->menu_order = $validated['menu_order'] ?? 0;

        // Загрузка баннера
        if ($request->hasFile('banner_image')) {
            $path = $request->file('banner_image')->store('categories/banners', 'public');
            $category->banner_image = $path;
        }

        if (!empty($validated['parent_id'])) {
            $parent = Category::findOrFail($validated['parent_id']);
            $category->appendToNode($parent)->save();
        } else {
            $category->save();
        }

        if (!empty($validated['product_ids'])) {
            $category->products()->attach($validated['product_ids']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Категория успешно создана.',
            'data' => CategoryResource::make($category),
        ], 201);
    }

    public function update(Category $category, Request $request)
    {

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:categories,id',
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'exists:products,id',

            'show_in_catalog_menu' => 'nullable|boolean',
            'show_as_home_banner' => 'nullable|boolean',
            'is_new_product' => 'nullable|boolean',
            'menu_order' => 'nullable|integer|min:0',
            'banner_image' => 'nullable|image|max:5120',
            'remove_banner_image' => 'nullable|boolean',
        ]);

        $category->name = $validated['name'];
        $category->description = $validated['description'] ?? null;
        $category->show_in_catalog_menu = $validated['show_in_catalog_menu'] ?? $category->show_in_catalog_menu;
        $category->show_as_home_banner = $validated['show_as_home_banner'] ?? $category->show_as_home_banner;
        $category->is_new_product = $validated['is_new_product'] ?? $category->is_new_product;
        $category->menu_order = $validated['menu_order'] ?? $category->menu_order;

        // Удаление старого баннера если загружен новый или если запрошено удаление
        if ($request->hasFile('banner_image') || $request->boolean('remove_banner_image')) {
            if ($category->banner_image && Storage::disk('public')->exists($category->banner_image)) {
                Storage::disk('public')->delete($category->banner_image);
            }
            $category->banner_image = null;
        }

        // Загрузка нового баннера
        if ($request->hasFile('banner_image')) {
            $path = $request->file('banner_image')->store('categories/banners', 'public');
            $category->banner_image = $path;
        }

        if (!empty($validated['parent_id']) && $validated['parent_id'] !== $category->parent_id) {
            if (!empty($validated['parent_id'])) {
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
            'success' => true,
            'message' => 'Категория успешно обновлена',
            'data' => CategoryResource::make($category),
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

        $category = Category::where('id', $validated['category_id'])->first();

        $products = [];

        if ($category) {
            $category_children_ids = Category::whereNotNull('parent_id')
                ->where('parent_id', $category->id)
                ->pluck('id')
                ->toArray();

            $category_products_ids = CategoryProduct::where('category_id', $category->id)
                ->orWhereIn('category_id', $category_children_ids)
                ->pluck('product_id')
                ->toArray();

            $products = Product::whereIn('id', $category_products_ids)->get();
        }

        return response()->json([
            'category_id' => $category->id,
            'category_name' => $category->name,
            'products' => ProductNumberTwoResouce::collection($products),
        ]);
    }


    /**
     * Получить URL изображения баннера категории
     * GET /api/categories/{category}/banner-image
     */
    public function getBannerImage(Category $category)
    {
        if (!$category->banner_image) {
            return response()->json([
                'success' => false,
                'message' => 'У категории нет баннера'
            ], 404);
        }

        $url = \Storage::disk('public')->url($category->banner_image);

        return response()->json([
            'success' => true,
            'url' => $url
        ]);
    }

}
