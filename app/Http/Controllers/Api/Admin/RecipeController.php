<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\CostCategoryResource;
use App\Models\CostCategory;
use App\Models\Material;
use App\Models\Product;
use App\Models\ProductRecipe;
use App\Models\ProductVariant;
use App\Models\Recipe;
use App\Models\Unit;
use App\Services\ProductionCostService;
use App\Services\RecipeService;
use App\Traits\RecipeTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RecipeController extends Controller
{
    use RecipeTrait;
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
            'items.component.inventoryBalance',
            'items.unit',
            'outputUnit',
            'createdBy',
            'costRates.category',
            'output_products.product',
            'output_products.product_variant',
        ])->whereNull('deleted_at');

        if ($request->get('recipe_id')) {
            $recipes = $recipes->where('id', $request->get('recipe_id'))->get();
            if (count($recipes) >= 1) {
                $recipes = $this->solve_category_cost($recipes[0]);
            }
        } else if ($request->get('per_page')) {
            $recipes = $recipes->paginate($request->get('per_page'));
        } else {
            $recipes = $recipes->get();
        }

        if (!$request->get('recipe_id')) {
            foreach ($recipes as &$recipe) {
                $recipe = $this->solve_category_cost($recipe);
            }
        }

        return response()->json([
            'recipes' => $recipes,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/recipes",
     *     operationId="createRecipe",
     *     tags={"Recipes"},
     *     summary="Create a new recipe",
     *     description="Creates a new recipe with related items, products, and cost rates.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 type="object",
     *                 required={"name", "output_quantity", "output_unit_id", "items", "products", "cost_rates"},
     *                 @OA\Property(property="name", type="string", description="Name of the recipe"),
     *                 @OA\Property(property="description", type="string", nullable=true, description="Description of the recipe"),
     *                 @OA\Property(property="output_quantity", type="number", format="float", description="Quantity produced by the recipe"),
     *                 @OA\Property(property="output_unit_id", type="integer", description="ID of the unit used for output quantity"),
     *                 @OA\Property(property="instructions", type="string", nullable=true, description="Instructions for making the recipe"),
     *                 @OA\Property(property="production_time", type="integer", nullable=true, description="Time required to produce the recipe"),
     *                 @OA\Property(property="is_active", type="boolean", description="Whether the recipe is active"),
     *                 @OA\Property(
     *                     property="items",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         required={"component_type", "component_id", "quantity", "unit_id"},
     *                         @OA\Property(property="component_type", type="string", enum={"Material", "Product"}, description="The type of component (Material or Product)"),
     *                         @OA\Property(property="component_id", type="integer", description="The ID of the component"),
     *                         @OA\Property(property="quantity", type="number", format="float", description="Quantity of the component"),
     *                         @OA\Property(property="unit_id", type="integer", description="Unit ID for the component")
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="products",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         required={"product_id"},
     *                         @OA\Property(property="product_id", type="integer", description="ID of the product"),
     *                         @OA\Property(property="variant_id", type="integer", nullable=true, description="ID of the product variant"),
     *                         @OA\Property(property="is_default", type="boolean", description="Whether the product is the default")
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="cost_rates",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         required={"cost_category_id", "rate_per_unit", "fixed_rate"},
     *                         @OA\Property(property="cost_category_id", type="integer", description="ID of the cost category"),
     *                         @OA\Property(property="rate_per_unit", type="number", format="float", description="Cost rate per unit"),
     *                         @OA\Property(property="fixed_rate", type="number", format="float", description="Fixed cost rate")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Recipe created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Recipe")
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error"
     *     )
     * )
     */
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
            'items' => 'required|array|min:1',
            'items.*.component_type' => 'required|in:Material,Product,ProductVariant',
            'items.*.component_id' => 'required|integer',
            'items.*.quantity' => 'required|numeric|min:0.001',
            'items.*.unit_id' => 'required|exists:units,id',
            'products' => 'required|array|min:1',
            'products.*.component_type' => 'required|in:Material,Product,ProductVariant',
            'products.*.component_id' => 'required|integer',
            'products.*.is_default' => 'boolean',
            'products.*.qty' => 'required|numeric|min:0.001',
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

        foreach ($validated['items'] as $item) {
            $modelClass = match ($item['component_type']) {
                'ProductVariant' => ProductVariant::class, // this should come here for now
                'Product' => Product::class,
                'Material' => Material::class,
            };

            $recipe->items()->create([
                'component_type' => $modelClass,
                'component_id' => $item['component_id'],
                'quantity' => $item['quantity'],
                'unit_id' => $item['unit_id']
            ]);
        }

        $qty_total = 0.0;
        foreach ($validated['products'] as $productData) {
            $qty_total += $productData['qty'] ?? 0.0;

            $modelClass = match ($productData['component_type']) {
                'ProductVariant' => ProductVariant::class, // this should come here for now
                'Product' => Product::class,
                'Material' => Material::class,
            };
            
            ProductRecipe::create([
                "recipe_id" => $recipe->id,
                'component_type' => $modelClass,
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

    /**
     * @OA\Get(
     *     path="/recipes/{recipe}",
     *     operationId="getRecipe",
     *     tags={"Recipes"},
     *     summary="Get a specific recipe",
     *     description="Fetches details of a specific recipe by its ID, including related items and units.",
     *     @OA\Parameter(
     *         name="recipe",
     *         in="path",
     *         required=true,
     *         description="ID of the recipe to retrieve",
     *         @OA\Schema(type="integer", format="int64")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Recipe retrieved successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Recipe")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Recipe not found"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error"
     *     )
     * )
     */
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

    /**
     * @OA\Put(
     *     path="/recipes/{recipe}",
     *     operationId="updateRecipe",
     *     tags={"Recipes"},
     *     summary="Update a specific recipe",
     *     description="Updates the details of a specific recipe including items, products, and cost rates.",
     *     @OA\Parameter(
     *         name="recipe",
     *         in="path",
     *         required=true,
     *         description="ID of the recipe to update",
     *         @OA\Schema(type="integer", format="int64")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="name", type="string", maxLength=255),
     *             @OA\Property(property="description", type="string", nullable=true),
     *             @OA\Property(property="output_quantity", type="number", format="float", minimum=0.001),
     *             @OA\Property(property="output_unit_id", type="integer", format="int64"),
     *             @OA\Property(property="instructions", type="string", nullable=true),
     *             @OA\Property(property="production_time", type="integer", minimum=1, nullable=true),
     *             @OA\Property(property="is_active", type="boolean"),
     *             @OA\Property(
     *                 property="items",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="component_type", type="string", enum={"Material", "Product"}),
     *                     @OA\Property(property="component_id", type="integer", format="int64"),
     *                     @OA\Property(property="quantity", type="number", format="float", minimum=0.001),
     *                     @OA\Property(property="unit_id", type="integer", format="int64")
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="products",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="product_id", type="integer", format="int64"),
     *                     @OA\Property(property="variant_id", type="integer", format="int64", nullable=true),
     *                     @OA\Property(property="is_default", type="boolean", nullable=true)
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="cost_rates",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="cost_category_id", type="integer", format="int64"),
     *                     @OA\Property(property="rate_per_unit", type="number", format="float", minimum=0),
     *                     @OA\Property(property="fixed_rate", type="number", format="float", minimum=0)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Recipe updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Recipe")
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Recipe not found"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error"
     *     )
     * )
     */
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
            'items' => 'required|array|min:1',
            'items.*.component_type' => 'required|in:Material,Product,ProductVariant',
            'items.*.component_id' => 'required|integer',
            'items.*.quantity' => 'required|numeric|min:0.001',
            'items.*.unit_id' => 'required|exists:units,id',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.variant_id' => 'nullable|exists:product_variants,id',
            'products.*.is_default' => 'boolean',
            'products.*.qty' => 'required|numeric|min:0.001',
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

            $recipe->items()->delete(); // puts datetime in deleted_at field in table
            foreach ($validated['items'] as $item) {
                $modelClass = match ($item['component_type']) {
                    'ProductVariant' => ProductVariant::class, // this should come here for now
                    'Product' => Product::class,
                    'Material' => Material::class,
                };

                $recipe->items()->create([
                    'component_type' => $modelClass,
                    'component_id' => $item['component_id'],
                    'quantity' => $item['quantity'],
                    'unit_id' => $item['unit_id']
                ]);
            }

            $recipe->products()->detach();
            $qty_total = 0.0;
            foreach ($validated['products'] as $productData) {
                $qty_total += $productData['qty'] ?? 0.0;
                $recipe->products()->attach($productData['product_id'], [
                    'product_variant_id' => $productData['variant_id'],
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

    /**
     * @OA\Delete(
     *     path="/recipes/{recipe}",
     *     operationId="deleteRecipe",
     *     tags={"Recipes"},
     *     summary="Delete a specific recipe",
     *     description="Deletes a specific recipe, ensuring it is not used in any production batches and not the only recipe for any product.",
     *     @OA\Parameter(
     *         name="recipe",
     *         in="path",
     *         required=true,
     *         description="ID of the recipe to delete",
     *         @OA\Schema(type="integer", format="int64")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Recipe successfully deleted",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Recipe successfully deleted")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation or business logic error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Cannot delete recipe used in production batches")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Recipe not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Recipe not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Failed to delete recipe: Some error message")
     *         )
     *     )
     * )
     */
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
            (float) $validated['quantity']
        );

        $productionCosts = $this->productionCostService->calculateEstimatedCosts(
            $recipe,
            (float) $validated['quantity']
        );

        $totalCost = $materialsCost['materials_cost'] +
            $productionCosts['labor'] +
            $productionCosts['overhead'] +
            $productionCosts['management'];

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
