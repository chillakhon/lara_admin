<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Image;
use App\Models\Option;
use App\Models\OptionValue;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class OptionController extends Controller
{
    protected $imageService;

    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }

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

            DB::commit();

            return response()->json(['message' => 'Опция успешно создана'], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            logger()->error('Error creating option:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'Произошла ошибка при создании опции'], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/options/{option}",
     *     summary="Обновить опцию",
     *     tags={"Options"},
     *     @OA\Parameter(
     *         name="option",
     *         in="path",
     *         description="ID опции для обновления",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/Option")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Опция успешно обновлена",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Опция успешно обновлена")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Ошибка при обновлении опции",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Произошла ошибка при обновлении опции")
     *         )
     *     )
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

            $option->update([
                'name' => $validated['name'],
                'category_id' => $validated['category_id'],
                'is_required' => $validated['is_required'],
                'order' => $validated['order'],
            ]);

            $currentValueIds = $option->values()->pluck('id')->toArray();
            $newValueIds = collect($validated['values'])->pluck('id')->filter()->toArray();

            $valuesToDelete = array_diff($currentValueIds, $newValueIds);

            foreach ($valuesToDelete as $valueId) {
                if ($value = OptionValue::find($valueId)) {
                    $this->deleteOptionValue($value);
                }
            }

            foreach ($validated['values'] as $valueData) {
                if (!empty($valueData['id'])) {
                    $value = OptionValue::find($valueData['id']);
                    if ($value) {
                        $value->update([
                            'name' => $valueData['name'],
                            'value' => $valueData['value'] ?? $valueData['name'],
                            'color_code' => $valueData['color_code'] ?? null,
                            'order' => $valueData['order'],
                        ]);

                        if (!empty($valueData['delete_image'])) {
                            foreach ($value->images as $image) {
                                $this->imageService->deleteImage($image->path);
                                $image->delete();
                            }
                        }

                        if (isset($valueData['image'])) {
                            foreach ($value->images as $image) {
                                $this->imageService->deleteImage($image->path);
                                $image->delete();
                            }

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
                    $value = $option->values()->create([
                        'name' => $valueData['name'],
                        'value' => $valueData['value'] ?? $valueData['name'],
                        'color_code' => $valueData['color_code'] ?? null,
                        'order' => $valueData['order'],
                    ]);

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

            return response()->json(['message' => 'Опция успешно обновлена']);
        } catch (\Exception $e) {
            DB::rollBack();
            logger()->error('Error updating option:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'Произошла ошибка при обновлении опции'], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/options/{option}",
     *     summary="Удалить опцию",
     *     tags={"Options"},
     *     @OA\Parameter(
     *         name="option",
     *         in="path",
     *         description="ID опции для удаления",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Опция успешно удалена",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Опция успешно удалена")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Ошибка при удалении опции",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Произошла ошибка при удалении опции")
     *         )
     *     )
     * )
     */

    public function destroy(Option $option)
    {
        try {
            DB::beginTransaction();

            foreach ($option->values as $value) {
                $this->deleteOptionValue($value);
            }

            $option->delete();

            DB::commit();
            return response()->json(['message' => 'Опция успешно удалена']);
        } catch (\Exception $e) {
            DB::rollBack();
            logger()->error('Error deleting option:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'Произошла ошибка при удалении опции'], 500);
        }
    }

    private function deleteOptionValue($value)
    {
        try {
            DB::beginTransaction();

            foreach ($value->images as $image) {
                $this->imageService->deleteImage($image->path);
                $image->delete();
            }

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
