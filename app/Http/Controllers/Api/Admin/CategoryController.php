<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inertia\Inertia;

class CategoryController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/categories",
     *     summary="Получить список категорий",
     *     tags={"Categories"},
     *     @OA\Response(
     *         response=200,
     *         description="Успешный ответ",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Category"))
     *     )
     * )
     */
    public function index()
    {
        $categories = Category::withDepth()->defaultOrder()->get()->toTree();
        return response()->json(['categories' => $categories]);
    }

    /**
     * @OA\Post(
     *     path="/api/categories",
     *     summary="Создать категорию",
     *     tags={"Categories"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="Новая категория"),
     *             @OA\Property(property="parent_id", type="integer", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Категория создана",
     *         @OA\JsonContent(ref="#/components/schemas/Category")
     *     )
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:categories,id'
        ]);

        $category = new Category();
        $category->name = $validated['name'];

        if (!empty($validated['parent_id'])) {
            $parent = Category::findOrFail($validated['parent_id']);
            $category->appendToNode($parent)->save();
        } else {
            $category->save();
        }

        return response()->json(['message' => 'Category created successfully', 'category' => $category], Response::HTTP_CREATED);
    }

    /**
     * @OA\Put(
     *     path="/api/categories/{id}",
     *     summary="Обновить категорию",
     *     tags={"Categories"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="Обновленная категория"),
     *             @OA\Property(property="parent_id", type="integer", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Категория обновлена",
     *         @OA\JsonContent(ref="#/components/schemas/Category")
     *     )
     * )
     */
    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:categories,id'
        ]);

        $category->name = $validated['name'];

        if ($validated['parent_id'] !== $category->parent_id) {
            if (!empty($validated['parent_id'])) {
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

    /**
     * @OA\Delete(
     *     path="/api/categories/{id}",
     *     summary="Удалить категорию",
     *     tags={"Categories"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Категория удалена",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Category deleted successfully.")
     *         )
     *     )
     * )
     */
    public function destroy(Category $category)
    {
        $category->delete();
        return response()->json(['message' => 'Category deleted successfully.']);
    }
}
