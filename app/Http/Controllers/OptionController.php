<?php

namespace App\Http\Controllers;

use App\Models\Image;
use App\Models\Option;
use App\Models\Category;
use App\Models\OptionValue;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

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

        return Inertia::render('Dashboard/Options/Index', [
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

            return redirect()->back()->with('success', 'Опция успешно создана');
        } catch (\Exception $e) {
            DB::rollBack();
            logger()->error('Error creating option:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->with('error', 'Произошла ошибка при создании опции');
        }
    }

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

            return redirect()->back()->with('success', 'Опция успешно обновлена');
        } catch (\Exception $e) {
            DB::rollBack();
            logger()->error('Error updating option:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->with('error', 'Произошла ошибка при обновлении опции');
        }
    }

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
            return redirect()->back()->with('success', 'Опция успешно удалена');
        } catch (\Exception $e) {
            DB::rollBack();
            logger()->error('Error deleting option:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->with('error', 'Произошла ошибка при удалении опции');
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
