<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Image;
use App\Models\Material;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Unit;
use App\Services\MaterialService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ProductController extends Controller
{
    protected $materialService;

    public function __construct(MaterialService $materialService)
    {
        $this->materialService = $materialService;
    }

    public function index(Request $request)
    {
        $query = Product::query()
            ->with(['categories', 'options', 'variants'])
            ->when($request->search, function ($query, $search) {
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhereHas('categories', function($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->when($request->category, function($query, $categoryId) {
                $query->whereHas('categories', function($q) use ($categoryId) {
                    $q->where('categories.id', $categoryId);
                });
            });

        $products = $query->latest()->paginate(10)->withQueryString();

        return Inertia::render('Dashboard/Products/Index', [
            'products' => $products,
            'categories' => Category::select(['id', 'name'])->get(),
            'filters' => [
                'search' => $request->search,
                'category' => $request->category
            ],
            'units' => Unit::all()
        ]);
    }

    public function show(Product $product)
    {
        $product->load([
            'categories',
            'options.values',
            'variants.optionValues.option',
            'variants.images',
            'variants.unit',
            'defaultUnit'
        ]);

        return Inertia::render('Dashboard/Products/Show', [
            'product' => $product,
            'categories' => Category::with('options.values')->get(),
            'units' => Unit::all(),
        ]);
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

        $product = Product::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'description' => $validated['description'],
            'type' => $validated['type'],
            'default_unit_id' => $validated['default_unit_id'],
            'is_active' => $validated['is_active'],
            'has_variants' => $validated['has_variants'],
            'allow_preorder' => $validated['allow_preorder'],
            'after_purchase_processing_time' => $validated['after_purchase_processing_time'],
        ]);

        $product->categories()->sync($validated['categories']);

        return redirect()->route('dashboard.products.show', $product)
            ->with('success', 'Товар успешно создан');
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

        // Преобразуем массив опций в формат для sync
        $options = collect($request->options ?? [])->mapWithKeys(function ($option) {
            return [$option['option_id'] => ['is_required' => $option['is_required']]];
        })->all();

        // Синхронизируем опции
        $product->options()->sync($options);

        return redirect()->back()->with('success', 'Товар успешно обновлен');
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


    public function storeOption(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'is_required' => 'boolean',
        ]);

        $product->options()->create($validated);

        return redirect()->back()->with('success', 'Опция успешно добавлена');
    }

    public function updateOption(Request $request, Product $product, $optionId)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'is_required' => 'boolean',
        ]);

        $product->options()->findOrFail($optionId)->update($validated);

        return redirect()->back()->with('success', 'Опция успешно обновлена');
    }

    public function destroyOption(Product $product, $optionId)
    {
        $product->options()->findOrFail($optionId)->delete();

        return redirect()->back()->with('success', 'Опция успешно удалена');
    }

    // Методы для работы с вариантами
    public function generateVariants(Request $request, Product $product)
    {
        $validated = $request->validate([
            'variants' => 'required|array',
            'variants.*.name' => 'required|string',
            'variants.*.sku' => 'required|string|unique:product_variants,sku',
            'variants.*.price' => 'required|numeric|min:0',
            'variants.*.option_values' => 'required|array',
            'variants.*.option_values.*' => 'exists:option_values,id'
        ]);

        foreach ($validated['variants'] as $variantData) {
            $variant = $product->variants()->create([
                'name' => $variantData['name'],
                'sku' => $variantData['sku'],
                'price' => $variantData['price'],
                'type' => $product->type,
                'unit_id' => $product->default_unit_id,
                'is_active' => true
            ]);

            $variant->optionValues()->sync($variantData['option_values']);
        }

        return redirect()->back()->with('success', 'Варианты успешно сгенерированы');
    }

    public function storeImages(Request $request, Product $product)
    {
        $request->validate([
            'images' => 'required|array',
            'images.*' => 'required|image|max:5120', // 5MB max
            'variant_id' => 'nullable|exists:product_variants,id',
            'is_main' => 'boolean'
        ]);

        $uploadedImages = [];

        foreach ($request->file('images') as $image) {
            $path = $image->store('products', 'public');

            $imageModel = new Image([
                'path' => $path,
                'url' => Storage::url($path),
                'is_main' => $request->is_main,
                'order' => 0
            ]);

            if ($request->variant_id) {
                // Если указан вариант, прикрепляем изображение к варианту
                $variant = ProductVariant::find($request->variant_id);
                $variant->images()->save($imageModel);
            } else {
                // Иначе прикрепляем к основному продукту
                $product->images()->save($imageModel);
            }

            $uploadedImages[] = $imageModel;
        }

        // Если загружаем основное изображение, сбрасываем флаг у остальных
        if ($request->is_main) {
            if ($request->variant_id) {
                $variant = ProductVariant::find($request->variant_id);
                $variant->images()->where('id', '!=', $uploadedImages[0]->id)->update(['is_main' => false]);
            } else {
                $product->images()->where('id', '!=', $uploadedImages[0]->id)->update(['is_main' => false]);
            }
        }

        return redirect()->back()->with('success', 'Изображения успешно загружены');
    }


    public function deleteImage(Product $product, $imageId)
    {
        // Находим изображение
        $image = Image::findOrFail($imageId);

        // Проверяем, принадлежит ли изображение продукту или его вариантам
        $belongs = $product->images()->where('images.id', $imageId)->exists() ||
            $product->variants()->whereHas('images', function($q) use ($imageId) {
                $q->where('images.id', $imageId);
            })->exists();

        if (!$belongs) {
            abort(403);
        }

        // Удаляем файл
        Storage::disk('public')->delete($image->path);

        // Удаляем запись
        $image->delete();

        return redirect()->back()->with('success', 'Изображение удалено');
    }

    public function setMainImage(Product $product, $imageId)
    {
        // Находим изображение
        $image = Image::findOrFail($imageId);

        // Проверяем, принадлежит ли изображение продукту или его вариантам
        $belongs = $product->images()->where('images.id', $imageId)->exists() ||
            $product->variants()->whereHas('images', function($q) use ($imageId) {
                $q->where('images.id', $imageId);
            })->exists();

        if (!$belongs) {
            abort(403);
        }

        // Определяем, к чему привязано изображение
        $isVariantImage = $product->variants()->whereHas('images', function($q) use ($imageId) {
            $q->where('images.id', $imageId);
        })->exists();

        if ($isVariantImage) {
            // Если изображение принадлежит варианту, обновляем флаг только у изображений этого варианта
            $variant = $product->variants()->whereHas('images', function($q) use ($imageId) {
                $q->where('images.id', $imageId);
            })->first();

            $variant->images()->update(['is_main' => false]);
            $image->update(['is_main' => true]);
        } else {
            // Если изображение принадлежит продукту, обновляем флаг у изображений продукта
            $product->images()->update(['is_main' => false]);
            $image->update(['is_main' => true]);
        }

        return redirect()->back()->with('success', 'Основное изображение обновлено');
    }

    public function reorderImages(Request $request, Product $product)
    {
        $request->validate([
            'images' => 'required|array',
            'images.*.id' => 'required|exists:images,id',
            'images.*.order' => 'required|integer|min:0'
        ]);

        foreach ($request->images as $imageData) {
            $image = Image::findOrFail($imageData['id']);

            // Проверяем принадлежность изображения
            $belongs = $product->images()->where('images.id', $image->id)->exists() ||
                $product->variants()->whereHas('images', function($q) use ($image) {
                    $q->where('images.id', $image->id);
                })->exists();

            if ($belongs) {
                $image->update(['order' => $imageData['order']]);
            }
        }

        return redirect()->back()->with('success', 'Порядок изображений обновлен');
    }

    public function attachOptions(Request $request, Product $product)
    {
        $validated = $request->validate([
            'options' => 'required|array',
            'options.*.option_id' => 'required|exists:options,id',
            'options.*.is_required' => 'required|boolean',
        ]);

        // Используем attach() вместо sync()
        foreach ($validated['options'] as $optionData) {
            // Проверяем, не существует ли уже такая связь
            if (!$product->options()->where('option_id', $optionData['option_id'])->exists()) {
                $product->options()->attach($optionData['option_id'], [
                    'is_required' => $optionData['is_required']
                ]);
            }
        }

        return redirect()->back()->with('success', 'Опции успешно добавлены к товару');
    }
}
