<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
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

class RecipeController extends Controller
{
    protected $recipeService;
    protected $productionCostService;

    public function __construct(
        RecipeService         $recipeService,
        ProductionCostService $productionCostService
    )
    {
        $this->recipeService = $recipeService;
        $this->productionCostService = $productionCostService;
    }

    public function index()
    {
        $recipes = Recipe::with([
            'products' => function ($query) {
                $query->with(['variants']);
            },
            'selectedVariants',
            'items.component.inventoryBalance',
            'items.unit',
            'outputUnit',
            'createdBy',
            'costRates.category'
        ])->get();

        return response()->json($recipes);
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

        foreach ($validated['items'] as $item) {
            $recipe->items()->create([
                'component_type' => $item['component_type'],
                'component_id' => $item['component_id'],
                'quantity' => $item['quantity'],
                'unit_id' => $item['unit_id']
            ]);
        }

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

        return response()->json($recipe, 201);
    }

    public function show(Recipe $recipe)
    {
        return response()->json($recipe->load(['items.component', 'items.unit']));
    }

    public function update(Request $request, Recipe $recipe)
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

        DB::beginTransaction();
        try {
            $recipe->update([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'output_quantity' => $validated['output_quantity'],
                'output_unit_id' => $validated['output_unit_id'],
                'instructions' => $validated['instructions'] ?? null,
                'production_time' => $validated['production_time'] ?? null,
                'is_active' => $validated['is_active'] ?? true
            ]);

            $recipe->items()->delete();
            foreach ($validated['items'] as $item) {
                $recipe->items()->create([
                    'component_type' => $item['component_type'],
                    'component_id' => $item['component_id'],
                    'quantity' => $item['quantity'],
                    'unit_id' => $item['unit_id']
                ]);
            }

            $recipe->products()->detach();
            foreach ($validated['products'] as $productData) {
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
            return response()->json($recipe);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Ошибка при обновлении рецепта: ' . $e->getMessage()], 500);
        }
    }

    public function destroy(Recipe $recipe)
    {
        DB::beginTransaction();
        try {
            if ($recipe->productionBatches()->exists()) {
                throw new \Exception('Невозможно удалить рецепт, который используется в производственных партиях');
            }

            $productsWithSingleRecipe = $recipe->products()
                ->whereDoesntHave('recipes', function ($query) use ($recipe) {
                    $query->where('recipes.id', '!=', $recipe->id);
                })
                ->exists();

            if ($productsWithSingleRecipe) {
                throw new \Exception('Невозможно удалить единственный рецепт для продукта');
            }

            $recipe->products()->detach();
            $recipe->items()->delete();
            $recipe->delete();

            DB::commit();
            return response()->json(['message' => 'Рецепт успешно удален']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Не удалось удалить рецепт: ' . $e->getMessage()], 500);
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

        $materialsCost = $this->recipeService->calculateEstimatedCost(
            $recipe,
            $validated['strategy'],
            (float)$validated['quantity']
        );

        $productionCosts = $this->productionCostService->calculateEstimatedCosts(
            $recipe,
            (float)$validated['quantity']
        );

        $totalCost = $materialsCost['materials_cost'] +
            $productionCosts['labor'] +
            $productionCosts['overhead'] +
            $productionCosts['management'];

        $quantity = (float)$validated['quantity'];
        $costPerUnit = $quantity > 0 ? $totalCost / $quantity : 0;

        return response()->json([
            'materials_cost' => $materialsCost['materials_cost'],
            'materials_details' => $materialsCost['components'],
            'labor_cost' => $productionCosts['labor'],
            'overhead_cost' => $productionCosts['overhead'],
            'management_cost' => $productionCosts['management'],
            'total_cost' => $totalCost,
            'cost_per_unit' => $costPerUnit,
            'cost_details' => array_merge(
                array_map(function ($component) {
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

        $recipe->costRates()->delete();
        $recipe->costRates()->createMany($validated['cost_rates']);

        return response()->json(['message' => 'Ставки затрат успешно обновлены']);
    }
}
