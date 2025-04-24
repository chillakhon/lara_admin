<?php

namespace App\Http\Controllers;

use App\Http\Resources\CostCategoryResource;
use App\Models\CostCategory;
use App\Models\Material;
use App\Models\Product;
use App\Models\Recipe;
use App\Models\Unit;
use App\Services\ProductionCostService;
use App\Services\RecipeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class RecipeController extends Controller
{
    protected $recipeService;
    protected $productionCostService;

    public function __construct(
        RecipeService $recipeService,
        ProductionCostService $productionCostService
    )
    {
        $this->recipeService = $recipeService;
        $this->productionCostService = $productionCostService;
    }

    public function index()
    {
        $recipes = Recipe::with([
            'products' => function($query) {
                $query->with(['variants']);
            },
            'selectedVariants',
            'items.component.inventoryBalance',
            'items.unit',
            'outputUnit',
            'createdBy',
            'costRates.category' // Оставляем для будущих записей
        ])->get();

        // Отдельно получаем все доступные категории затрат
        $costCategories = CostCategory::where('is_active', true)
            ->orderBy('type')
            ->orderBy('name')
            ->get()
            ->map(function($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'type' => $category->type,
                    'type_name' => CostCategory::getTypes()[$category->type] ?? $category->type,
                    'description' => $category->description
                ];
            });

        Log::info('Available cost categories:', [
            'categories' => $costCategories->toArray()
        ]);

        return Inertia::render('Dashboard/Recipes/Index', [
            'recipes' => $recipes,
            'products' => Product::with('variants')->get(),
            'materials' => Material::with(['inventoryBalance', 'unit'])->get(),
            'units' => Unit::all(),
            'costCategories' => $costCategories // Передаем категории затрат
        ]);
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'output_quantity' => 'required|numeric|min:0.001',
            'output_unit_id' => 'required|exists:units,id',
            'instructions' => 'nullable|string',
            'production_time' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
            'items' => 'required|array|min:1',
            'items.*.component_type' => 'required|in:Material,Product',
            'items.*.component_id' => 'required|integer',
            'items.*.quantity' => 'required|numeric|min:0.001',
            'items.*.unit_id' => 'required|exists:units,id',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.variant_id' => 'nullable|exists:product_variants,id',
            'products.*.is_default' => 'boolean',
            'cost_rates' => 'array',
            'cost_rates.*.cost_category_id' => 'required|exists:cost_categories,id',
            'cost_rates.*.rate_per_unit' => 'required|numeric|min:0',
            'cost_rates.*.fixed_rate' => 'required|numeric|min:0'
        ]);

        $recipe = Recipe::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'output_quantity' => $validated['output_quantity'],
            'output_unit_id' => $validated['output_unit_id'],
            'instructions' => $validated['instructions'] ?? null,
            'production_time' => $validated['production_time'] ?? null,
            'created_by' => auth()->id(),
            'is_active' => $validated['is_active'] ?? true
        ]);

        // Добавляем компоненты рецепта
        foreach ($validated['items'] as $item) {
            $recipe->material_items()->create([
                'component_type' => $item['component_type'],
                'component_id' => $item['component_id'],
                'quantity' => $item['quantity'],
                'unit_id' => $item['unit_id']
            ]);
        }

        // Привязываем продукты и варианты
        foreach ($validated['products'] as $productData) {
            $recipe->products()->attach($productData['product_id'], [
                'product_variant_id' => $productData['variant_id'] ?? null,
                'is_default' => $productData['is_default'] ?? false
            ]);
        }

        if (!empty($validated['cost_rates'])) {
            foreach ($validated['cost_rates'] as $rate) {
                $recipe->costRates()->create([
                    'cost_category_id' => $rate['cost_category_id'],
                    'rate_per_unit' => $rate['rate_per_unit'],
                    'fixed_rate' => $rate['fixed_rate']
                ]);
            }
        }

        return redirect()->route('dashboard.recipes.index')
            ->with('success', 'Рецепт успешно создан');
    }

    public function update(Request $request, Recipe $recipe)
    {
        Log::info('Updating recipe', ['request' => $request->all()]);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'output_quantity' => 'required|numeric|min:0.001',
            'output_unit_id' => 'required|exists:units,id',
            'instructions' => 'nullable|string',
            'production_time' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
            'items' => 'required|array|min:1',
            'items.*.component_type' => 'required|in:Material,Product',
            'items.*.component_id' => 'required|integer',
            'items.*.quantity' => 'required|numeric|min:0.001',
            'items.*.unit_id' => 'required|exists:units,id',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.variant_id' => 'nullable|exists:product_variants,id',
            'products.*.is_default' => 'boolean',
            'cost_rates' => 'array',
            'cost_rates.*.cost_category_id' => 'required|exists:cost_categories,id',
            'cost_rates.*.rate_per_unit' => 'required|numeric|min:0',
            'cost_rates.*.fixed_rate' => 'required|numeric|min:0'
        ]);

        try {
            DB::beginTransaction();

            // Обновляем основные данные рецепта
            $recipe->update([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'output_quantity' => $validated['output_quantity'],
                'output_unit_id' => $validated['output_unit_id'],
                'instructions' => $validated['instructions'] ?? null,
                'production_time' => $validated['production_time'] ?? null,
                'is_active' => $validated['is_active'] ?? true
            ]);

            // Обновляем компоненты
            $recipe->material_items()->delete();
            foreach ($validated['items'] as $item) {
                $recipe->material_items()->create([
                    'component_type' => $item['component_type'],
                    'component_id' => $item['component_id'],
                    'quantity' => $item['quantity'],
                    'unit_id' => $item['unit_id']
                ]);
            }

            // Удаляем старые связи с продуктами
            $recipe->products()->detach();

            // Удаляем дубликаты из массива продуктов
            $uniqueProducts = collect($validated['products'])->unique(function ($product) {
                return $product['product_id'] . '-' . $product['variant_id'];
            })->values()->all();

            // Добавляем новые связи
            foreach ($uniqueProducts as $productData) {
                $recipe->products()->attach($productData['product_id'], [
                    'product_variant_id' => $productData['variant_id'],
                    'is_default' => $productData['is_default'],
                ]);
            }

            $recipe->costRates()->delete();
            if (!empty($validated['cost_rates'])) {
                foreach ($validated['cost_rates'] as $rate) {
                    $recipe->costRates()->create([
                        'cost_category_id' => $rate['cost_category_id'],
                        'rate_per_unit' => $rate['rate_per_unit'],
                        'fixed_rate' => $rate['fixed_rate']
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('dashboard.recipes.index')
                ->with('success', 'Рецепт успешно обновлен');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating recipe', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()
                ->withErrors(['error' => 'Ошибка при обновлении рецепта: ' . $e->getMessage()])
                ->withInput();
        }
    }

    public function estimateCost(Request $request)
    {
        $validated = $request->validate([
            'recipe_id' => 'required|exists:recipes,id',
            'quantity' => 'required|numeric|min:0.001',
            'strategy' => 'required|string|in:average,fifo,lifo'
        ]);

        $recipe = Recipe::with(['items.component', 'costRates.category'])->findOrFail($validated['recipe_id']);

        // Получаем стоимость материалов
        $materialsCost = $this->recipeService->calculateEstimatedCost(
            $recipe,
            $validated['strategy'],
            (float) $validated['quantity']
        );

        // Получаем производственные затраты
        $productionCosts = $this->productionCostService->calculateEstimatedCosts(
            $recipe,
            (float) $validated['quantity']
        );

        // Рассчитываем общую стоимость
        $totalCost = $materialsCost['materials_cost'] +
            $productionCosts['labor'] +
            $productionCosts['overhead'] +
            $productionCosts['management'];

        $quantity = (float) $validated['quantity'];
        $costPerUnit = $quantity > 0 ? $totalCost / $quantity : 0;

        // Объединяем результаты
        return response()->json([
            'materials_cost' => $materialsCost['materials_cost'],
            'materials_details' => $materialsCost['components'],
            'labor_cost' => $productionCosts['labor'],
            'overhead_cost' => $productionCosts['overhead'],
            'management_cost' => $productionCosts['management'],
            'total_cost' => $totalCost,
            'cost_per_unit' => $costPerUnit,
            'cost_details' => array_merge(
                array_map(function($component) {
                    return [
                        'type' => 'material',
                        'name' => $component['name'],
                        'amount' => $component['total_cost']
                    ];
                }, $materialsCost['components']),
                $productionCosts['details']
            )
        ]);
    }

    public function getCostCategories()
    {
        $categories = CostCategory::where('is_active', true)
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        return CostCategoryResource::collection($categories);
    }

    public function storeCostRates(Request $request, Recipe $recipe)
    {
        $validated = $request->validate([
            'cost_rates' => 'required|array',
            'cost_rates.*.cost_category_id' => 'required|exists:cost_categories,id',
            'cost_rates.*.rate_per_unit' => 'required|numeric|min:0',
            'cost_rates.*.fixed_rate' => 'required|numeric|min:0'
        ]);

        $recipe->costRates()->delete(); // Удаляем старые ставки
        $recipe->costRates()->createMany($validated['cost_rates']);

        return response()->json(['message' => 'Ставки затрат успешно обновлены']);
    }



    public function show(Recipe $recipe)
    {
        // Рассчитываем стоимость по всем стратегиям для сравнения
        $costEstimations = collect($this->recipeService->getAvailableCostStrategies())
            ->mapWithKeys(function ($label, $strategy) use ($recipe) {
                return [
                    $strategy => $this->recipeService->calculateEstimatedCost($recipe, $strategy)
                ];
            });

        return Inertia::render('Dashboard/Recipes/Show', [
            'recipe' => $recipe->load(['items.component', 'items.unit']),
            'costEstimations' => $costEstimations,
            'availableCostStrategies' => $this->recipeService->getAvailableCostStrategies()
        ]);
    }

    public function destroy(Recipe $recipe)
    {
        try {
            DB::beginTransaction();

            // Проверяем, не используется ли рецепт в производстве
            if ($recipe->productionBatches()->exists()) {
                throw new \Exception('Невозможно удалить рецепт, который используется в производственных партиях');
            }

            // Проверяем, не является ли рецепт единственным для какого-либо продукта
            $productsWithSingleRecipe = $recipe->products()
                ->whereDoesntHave('recipes', function($query) use ($recipe) {
                    $query->where('recipes.id', '!=', $recipe->id);
                })
                ->exists();

            if ($productsWithSingleRecipe) {
                throw new \Exception('Невозможно удалить единственный рецепт для продукта');
            }

            // Удаляем связи с продуктами
            $recipe->products()->detach();

            // Удаляем компоненты рецепта
            $recipe->material_items()->delete();

            // Удаляем сам рецепт
            $recipe->delete();

            DB::commit();

            return redirect()->route('dashboard.recipes.index')
                ->with('success', 'Рецепт успешно удален');

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->route('dashboard.recipes.index')
                ->with('error', 'Не удалось удалить рецепт: ' . $e->getMessage());
        }
    }
}
