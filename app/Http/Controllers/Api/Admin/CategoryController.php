<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/categories",
     *     summary="Получить список корневых категорий",
     *     description="Возвращает список всех корневых категорий с их подкатегориями.",
     *     operationId="getCategories",
     *     tags={"Categories"},
     *     @OA\Response(
     *         response=200,
     *         description="Список категорий",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/Category")
     *         )
     *     )
     * )
     */
    public function index()
    {
        // Получаем только корневые категории с их потомками
        $categories = Category::with('children')
            ->whereIsRoot()
            ->defaultOrder()
            ->get();

        return CategoryResource::collection($categories);
    }

    /**
     * @OA\Post(
     *     path="/api/categories",
     *     summary="Создать новую категорию",
     *     description="Создает новую категорию и, при необходимости, привязывает её к родительской категории.",
     *     operationId="createCategory",
     *     tags={"Categories"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="New Category"),
     *             @OA\Property(property="parent_id", type="integer", nullable=true, example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Категория успешно создана",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Category created successfully."),
     *             @OA\Property(property="category", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="New Category"),
     *                 @OA\Property(property="slug", type="string", example="new-category"),
     *                 @OA\Property(property="parent_id", type="integer", nullable=true, example=1),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-03-08T12:00:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2025-03-08T12:00:00Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Ошибка валидации данных",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="The name field is required.")
     *         )
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
        // slug will be automatically generated

        if ($validated['parent_id']) {
            $parent = Category::findOrFail($validated['parent_id']);
            $category->appendToNode($parent)->save();
        } else {
            $category->save();
        }

        return response()->json([
            'message' => 'Category created successfully.',
            'category' => $category], 201);
    }

    /**
     * @OA\Put(
     *     path="/api/categories/{category}",
     *     summary="Обновить категорию",
     *     description="Обновляет существующую категорию, включая её имя и родительскую категорию.",
     *     operationId="updateCategory",
     *     tags={"Categories"},
     *     @OA\Parameter(
     *         name="category",
     *         in="path",
     *         description="ID категории",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="Updated Category"),
     *             @OA\Property(property="parent_id", type="integer", nullable=true, example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Категория успешно обновлена",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Category updated successfully."),
     *             @OA\Property(property="category", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Updated Category"),
     *                 @OA\Property(property="slug", type="string", example="updated-category"),
     *                 @OA\Property(property="parent_id", type="integer", nullable=true, example=1),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-03-08T12:00:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2025-03-08T12:00:00Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Категория не найдена",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Category not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Ошибка валидации данных",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="The name field is required.")
     *         )
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

    /**
     * @OA\Delete(
     *     path="/api/categories/{category}",
     *     summary="Удалить категорию",
     *     description="Удаляет категорию по её ID.",
     *     operationId="deleteCategory",
     *     tags={"Categories"},
     *     @OA\Parameter(
     *         name="category",
     *         in="path",
     *         description="ID категории для удаления",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Категория успешно удалена",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Category deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Категория не найдена",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Category not found")
     *         )
     *     )
     * )
     */
    public function destroy(Category $category)
    {
        $category->delete();
        return response()->json(['message' => 'Category deleted successfully']);
    }
}
