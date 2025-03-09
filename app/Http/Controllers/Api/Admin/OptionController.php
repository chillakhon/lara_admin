<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Image;
use App\Models\Option;
use App\Models\OptionValue;
use App\Services\ImageService;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class OptionController extends Controller
{
    protected $imageService;

    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }
    /**
     * @OA\Get(
     *     path="/options",
     *     summary="Получение списка опций",
     *     tags={"Опции"},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Поиск опций по имени",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="category",
     *         in="query",
     *         description="Фильтр по категории",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Успешный ответ",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="options", type="array", @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Цвет"),
     *                 @OA\Property(property="category", type="object",
     *                     @OA\Property(property="id", type="integer", example=2),
     *                     @OA\Property(property="name", type="string", example="Одежда")
     *                 ),
     *                 @OA\Property(property="values", type="array", @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=10),
     *                     @OA\Property(property="name", type="string", example="Красный"),
     *                     @OA\Property(property="value", type="string", example="#FF0000"),
     *                     @OA\Property(property="images", type="array", @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=5),
     *                         @OA\Property(property="url", type="string", example="https://example.com/image.jpg")
     *                     ))
     *                 ))
     *             )),
     *             @OA\Property(property="categories", type="array", @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Одежда")
     *             )),
     *             @OA\Property(property="filters", type="object",
     *                 @OA\Property(property="search", type="string", example=""),
     *                 @OA\Property(property="category", type="integer", example=null)
     *             )
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $query = Option::query()
            ->with(['category', 'values.images'])
            ->when($request->input('search'), function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->when($request->input('category'), function ($query, $category) {
                $query->where('category_id', $category);
            });

        $options = $query->orderBy('order')
            ->paginate(10)
            ->withQueryString();

        $categories = Category::select('id', 'name')->get();

        return response()->json([
            'options' => $options,
            'categories' => $categories,
            'filters' => $request->only(['search', 'category']),
        ]);
    }
    /**
     * @OA\Post(
     *     path="/options",
     *     summary="Создание новой опции",
     *     tags={"Опции"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "category_id"},
     *             @OA\Property(property="name", type="string", example="Размер"),
     *             @OA\Property(property="category_id", type="integer", example=2)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Опция создана")
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
        'name' => ['required', 'string', 'max:255'],
        'category_id' => ['required', 'exists:categories,id'],
        'is_required' => ['boolean'],
        'order' => ['integer', 'min:0'],
        'values' => ['required', 'array', 'min:1'],
        'values.*.name' => ['required', 'string', 'max:255'],
        'values.*.value' => ['nullable', 'string', 'max:255'],
        'values.*.color_code' => ['nullable', 'string', 'max:255'],
        'values.*.order' => ['required', 'integer', 'min:0'],
        'values.*.image' => ['nullable', 'image', 'max:2048'],
    ]);

        try {
            DB::beginTransaction();

            $option = Option::create([
                'name' => $validated['name'],
                'category_id' => $validated['category_id'],
                'is_required' => $validated['is_required'],
                'order' => $validated['order'],
            ]);

            foreach ($validated['values'] as $valueData) {
                $value = $option->values()->create([
                    'name' => $valueData['name'],
                    'value' => $valueData['value'] ?? $valueData['name'],
                    'color_code' => $valueData['color_code'] ?? null,
                    'order' => $valueData['order'],
                ]);

                if (isset($valueData['image'])) {
                    // Сохраняем изображение и создаем миниатюру
                    $paths = $this->imageService->saveImage(
                        $valueData['image'],
                        "options/values/{$option->id}",
                        200,
                        200
                    );

                    // Создаем запись об изображении
                    $image = Image::create([
                        'path' => $paths['original'],
                        'url' => $this->imageService->getImageUrl($paths['original']),
                        'is_main' => true,
                        'order' => 0
                    ]);

                    // Затем создаем связь через промежуточную таблицу
                    $value->images()->attach($image->id);
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Option created successfully',
                'option' => $option->load('values.images')
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            logger()->error('Error creating option:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Произошла ошибка при создании опции'], 500);
        }
    }
    /**
     * @OA\Put(
     *     path="/options/{id}",
     *     summary="Обновление опции",
     *     tags={"Опции"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Цвет")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Опция обновлена")
     * )
     */
    public function update(Request $request, Option $option)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'exists:categories,id'],
            'is_required' => ['boolean'],
            'order' => ['integer', 'min:0'],
            'values' => ['required', 'array', 'min:1'],
            'values.*.id' => ['nullable', 'exists:option_values,id'],
            'values.*.name' => ['required', 'string', 'max:255'],
            'values.*.value' => ['nullable', 'string', 'max:255'],
            'values.*.color_code' => ['nullable', 'string', 'max:255'],
            'values.*.order' => ['required', 'integer', 'min:0'],
            'values.*.image' => ['nullable', 'image', 'max:2048'],
            'values.*.delete_image' => ['nullable', 'boolean'],
        ]);

        try {
            DB::beginTransaction();

            // Обновляем основные данные опции
            $option->update([
                'name' => $validated['name'],
                'category_id' => $validated['category_id'],
                'is_required' => $validated['is_required'],
                'order' => $validated['order'],
            ]);

            $currentValueIds = $option->values()->pluck('id')->toArray();
            $newValueIds = collect($validated['values'])->pluck('id')->filter()->toArray();

            // Находим значения для удаления
            $valuesToDelete = array_diff($currentValueIds, $newValueIds);

            // Удаляем ненужные значения
            foreach ($valuesToDelete as $valueId) {
                if ($value = OptionValue::find($valueId)) {
                    $this->deleteOptionValue($value);
                }
            }

            // Обновляем или создаем значения опций
            foreach ($validated['values'] as $valueData) {
                if (!empty($valueData['id'])) {
                    // Обновляем существующее значение
                    $value = OptionValue::find($valueData['id']);
                    if ($value) {
                        $value->update([
                            'name' => $valueData['name'],
                            'value' => $valueData['value'] ?? $valueData['name'],
                            'color_code' => $valueData['color_code'] ?? null,
                            'order' => $valueData['order'],
                        ]);

                        // Обрабатываем удаление изображения
                        if (!empty($valueData['delete_image'])) {
                            foreach ($value->images as $image) {
                                $this->imageService->deleteImage($image->path);
                                $image->delete();
                            }
                        }

                        // Обрабатываем новое изображение
                        if (isset($valueData['image'])) {
                            // Удаляем старые изображения
                            foreach ($value->images as $image) {
                                $this->imageService->deleteImage($image->path);
                                $image->delete();
                            }

                            // Сохраняем новое изображение
                            $paths = $this->imageService->saveImage(
                                $valueData['image'],
                                "options/values/{$option->id}",
                                200,
                                200
                            );

                            $image = Image::create([
                                'path' => $paths['original'],
                                'url' => $this->imageService->getImageUrl($paths['original']),
                                'is_main' => true,
                                'order' => 0
                            ]);

                            $value->images()->attach($image->id);
                        }
                    }
                } else {
                    // Создаем новое значение
                    $value = $option->values()->create([
                        'name' => $valueData['name'],
                        'value' => $valueData['value'] ?? $valueData['name'],
                        'color_code' => $valueData['color_code'] ?? null,
                        'order' => $valueData['order'],
                    ]);

                    // Обрабатываем изображение для нового значения
                    if (isset($valueData['image'])) {
                        $paths = $this->imageService->saveImage(
                            $valueData['image'],
                            "options/values/{$option->id}",
                            200,
                            200
                        );

                        $image = Image::create([
                            'path' => $paths['original'],
                            'url' => $this->imageService->getImageUrl($paths['original']),
                            'is_main' => true,
                            'order' => 0
                        ]);

                        $value->images()->attach($image->id);
                    }
                }
            }

            DB::commit();
            return response()->json([
                'message' => 'Option updated successfully',
                'option' => $option
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            logger()->error('Error updating option:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Произошла ошибка при обновлении опции'], 500);
        }
    }
    /**
     * @OA\Delete(
     *     path="/options/{id}",
     *     summary="Удаление опции",
     *     tags={"Опции"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=204, description="Опция удалена")
     * )
     */
    public function destroy(Option $option)
    {
        try {
            DB::beginTransaction();

            // Удаляем все значения опции вместе с изображениями
            foreach ($option->values as $value) {
                $this->deleteOptionValue($value);
            }

            // Удаляем саму опцию
            $option->delete();

            DB::commit();
            return response()->json(['message' => 'Опция успешно удалена'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            logger()->error('Error deleting option:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Произошла ошибка при удалении опции'], 500);
        }
    }
    private function deleteOptionValue($value)
    {
        try {
            DB::beginTransaction();

            // Удаляем изображения
            foreach ($value->images as $image) {
                $this->imageService->deleteImage($image->path);
                $image->delete();
            }

            // Удаляем значение опции
            $value->delete();

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            logger()->error('Error deleting option value:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }
}
