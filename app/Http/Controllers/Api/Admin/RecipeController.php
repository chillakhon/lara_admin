<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\CostCategoryResource;
use App\Models\CostCategory;
use App\Models\ProductionBatch;
use App\Models\ProductRecipe;
use App\Models\Recipe;
use App\Services\ProductionCostService;
use App\Services\RecipeService;
use App\Traits\HelperTrait;
use App\Traits\RecipeTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RecipeController extends Controller
{
    use RecipeTrait, HelperTrait;


    protected $recipeService;
    protected $productionCostService;

    public function __construct(
        RecipeService $recipeService,
        ProductionCostService $productionCostService
    ) {
        $this->recipeService = $recipeService;
        $this->productionCostService = $productionCostService;
    }

    /**
     * @OA\Get(
     *     path="/recipes",
     *     operationId="getRecipes",
     *     tags={"Recipes"},
     *     summary="Get a list of all recipes with related data",
     *     description="Fetches all recipes with their related products, selected variants, items, and other associated data.",
     *     @OA\Response(
     *         response=200,
     *         description="List of recipes successfully fetched",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/Recipe")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error"
     *     )
     * )
     */
    public function index(Request $request)
    {
        $recipes = Recipe::with([
            'material_items.component.inventoryBalance',
            //            'material_items.unit',
//            'material_items.unit',
            'outputUnit',
            'createdBy' => function ($sql) {
                $sql->leftJoin(DB::raw('(
                    SELECT * FROM user_profiles AS up1
                        WHERE up1.id = (
                        SELECT MAX(up2.id)
                        FROM user_profiles AS up2
                        WHERE up2.user_id = up1.user_id
                    )
                    ) as user_profiles'), 'user_profiles.user_id', 'users.id')
                    ->select([
                        'users.id',
                        'users.email',
                        'user_profiles.first_name',
                        'user_profiles.last_name'
                    ]);
            },
            // 'costRates.category',
            'output_products.component.inventoryBalance',
        ])->whereNull('deleted_at');

        if ($request->get('recipe_id')) {
            $recipes = $recipes->where('id', $request->get('recipe_id'))->get();
            if (count($recipes) >= 1) {
                $recipes = $this->solve_category_cost($recipes[0]);
                $recipes = $this->get_parent_product_of_component($recipes);
            }
        } else if ($request->get('per_page')) {
            $recipes = $recipes->paginate($request->get('per_page'));
        } else {
            $recipes = $recipes->get();
        }

        if (!$request->get('recipe_id')) {
            // foreach ($recipes as &$recipe) {
            //     $recipe = $this->solve_category_cost($recipe);
            // }
        }

        if (!$request->get('recipe_id') || $request->get('per_page')) {
            foreach ($recipes as &$recipe) {
                $recipe = $this->get_parent_product_of_component($recipe);
            }
        }


        // return $recipes;
        // $this->change_items_model_type($recipes);

        return response()->json([
            'recipes' => $recipes,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            // 'output_quantity' => 'required|numeric|min:0.001',
            'output_unit_id' => 'required|exists:units,id',
            'instructions' => 'nullable|string',
            'production_time' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
            'material_items' => 'required|array|min:1',
            'material_items.*.component_type' => 'required|in:Material,Product,ProductVariant',
            'material_items.*.component_id' => 'required|integer',
            'material_items.*.quantity' => 'required|numeric|min:0.001',
            'material_items.*.unit_id' => 'required|exists:units,id',
            'output_products' => 'required|array|min:1',
            'output_products.*.component_type' => 'required|in:Material,Product,ProductVariant',
            'output_products.*.component_id' => 'required|integer',
            'output_products.*.is_default' => 'boolean',
            'output_products.*.qty' => 'required|numeric|min:0.001',
            'cost_rates' => 'array',
            'cost_rates.*.cost_category_id' => 'required|exists:cost_categories,id',
            'cost_rates.*.rate_per_unit' => 'nullable|numeric|min:0',
            'cost_rates.*.fixed_rate' => 'nullable|numeric|min:0'
        ]);

        $recipe = Recipe::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'output_quantity' => 0.0, // temporary value, then will be updated
            'output_unit_id' => $validated['output_unit_id'],
            'instructions' => $validated['instructions'] ?? null,
            'production_time' => $validated['production_time'] ?? null,
            'created_by' => auth()->id(),
            'is_active' => $validated['is_active'] ?? true
        ]);

        foreach ($validated['material_items'] as $item) {
            $recipe->material_items()->create([
                'component_type' => $this->get_model_by_type($item['component_type']),
                'component_id' => $item['component_id'],
                'quantity' => $item['quantity'],
                'unit_id' => $item['unit_id']
            ]);
        }

        $qty_total = 0.0;
        foreach ($validated['output_products'] as $productData) {
            $qty_total += $productData['qty'] ?? 0.0;

            ProductRecipe::create([
                "recipe_id" => $recipe->id,
                'component_type' => $this->get_model_by_type($productData['component_type']),
                'component_id' => $productData['component_id'],
                "qty" => $productData['qty'] ?? 0,
                'is_default' => $productData['is_default'],
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

        $recipe->update([
            'output_quantity' => $qty_total,
        ]);

        return response()->json($recipe, 201);
    }

    public function show(Recipe $recipe)
    {
        $recipe->load([
            'items.component',
            'items.unit',
            'costRates.category',
            'output_products.product',
            'output_products.product_variant',
        ]);

        $recipe = $this->solve_category_cost($recipe);

        return response()->json($recipe);
    }

    public function update(Request $request, Recipe $recipe)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            // 'output_quantity' => 'required|numeric|min:0.001',
            'output_unit_id' => 'required|exists:units,id',
            'instructions' => 'nullable|string',
            'production_time' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
            'material_items' => 'required|array|min:1',
            'material_items.*.component_type' => 'required|in:Material,Product,ProductVariant',
            'material_items.*.component_id' => 'required|integer',
            'material_items.*.quantity' => 'required|numeric|min:0.001',
            'material_items.*.unit_id' => 'required|exists:units,id',
            'output_products' => 'required|array|min:1',
            'output_products.*.component_type' => 'required|in:Material,Product,ProductVariant',
            'output_products.*.component_id' => 'required|integer',
            'output_products.*.is_default' => 'boolean',
            'output_products.*.qty' => 'required|numeric|min:0.001',
            'cost_rates' => 'array',
            'cost_rates.*.cost_category_id' => 'required|exists:cost_categories,id',
            'cost_rates.*.rate_per_unit' => 'nullable|numeric|min:0',
            'cost_rates.*.fixed_rate' => 'nullable|numeric|min:0'
        ]);

        DB::beginTransaction();
        try {
            $recipe->update([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'output_quantity' => 0.0, // temporary value, then will be updated
                'output_unit_id' => $validated['output_unit_id'],
                'instructions' => $validated['instructions'] ?? null,
                'production_time' => $validated['production_time'] ?? null,
                'is_active' => $validated['is_active'] ?? true
            ]);

            $recipe->material_items()->delete(); // puts datetime in deleted_at field in table
            foreach ($validated['material_items'] as $item) {
                $recipe->material_items()->create([
                    'component_type' => $this->get_model_by_type($item['component_type']),
                    'component_id' => $item['component_id'],
                    'quantity' => $item['quantity'],
                    'unit_id' => $item['unit_id']
                ]);
            }

            $recipe->products()->detach();
            $qty_total = 0.0;
            foreach ($validated['output_products'] as $productData) {
                $qty_total += $productData['qty'] ?? 0.0;
                ProductRecipe::create([
                    "recipe_id" => $recipe->id,
                    'component_type' => $this->get_model_by_type($item['component_type']),
                    'component_id' => $productData['component_id'],
                    "qty" => $productData['qty'] ?? 0,
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

            $recipe->update([
                'output_quantity' => $qty_total,
            ]);

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

            // Check if the recipe is used in any production batches
            $production_batches_that_use_this_recipe = ProductionBatch
                ::where('recipe_id', $recipe->id)
                ->get()
                ->count();

            if ($production_batches_that_use_this_recipe >= 1) {
                throw new \Exception('Невозможно удалить рецепт, который используется в производственных партиях');
            }

            // I didn't understand what this part is doing

            // $productsWithSingleRecipe = $recipe->products()
            //     ->whereDoesntHave('recipes', function ($query) use ($recipe) {
            //         $query->where('recipes.id', '!=', $recipe->id);
            //     })
            //     ->exists();

            // if ($productsWithSingleRecipe) {
            //     throw new \Exception('Невозможно удалить единственный рецепт для продукта');
            // }

            // $recipe->output_products()->delete();
            // $recipe->material_items()->delete();
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
            (float) $validated['quantity'],
            (float) $validated['quantity']
        );

        $productionCosts = $this->productionCostService->calculateEstimatedCosts(
            $recipe,
            (float) $validated['quantity'],
            (float) $validated['quantity']
        );

        $totalCost = $materialsCost['materials_cost'] +
            $productionCosts['labor'] +
            $productionCosts['overhead'] +
            $productionCosts['management'];

        $quantity = (float) $validated['quantity'];
        $quantity = (float) $validated['quantity'];
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
