<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Recipe;
use App\Services\ProductionService;
use Carbon\Carbon;
use Inertia\Inertia;

/**
 * @OA\Info(title="Production API", version="1.0.0")
 */
class ProductionController extends Controller
{
    protected $productionService;

    public function __construct(ProductionService $productionService)
    {
        $this->productionService = $productionService;
    }

    /**
     * @OA\Get(
     *     path="/production",
     *     operationId="getProductionBatches",
     *     tags={"Production"},
     *     summary="Get all production batches",
     *     @OA\Response(
     *         response=200,
     *         description="List of production batches",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/ProductionBatch"))
     *         )
     *     )
     * )
     */
    public function index()
    {
        $batches = ProductionBatch::with([
            'recipe.productVariant.product',
            'recipe.outputUnit',
            'createdBy'
        ])
            ->latest()
            ->paginate(20);

        return response()->json([
            'data' => $batches
        ]);
    }
    /**
     * @OA\Post(
     *     path="/production/batches",
     *     operationId="createProductionBatch",
     *     tags={"Production"},
     *     summary="Создать новую производственную партию",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"quantity"},
     *             @OA\Property(property="quantity", type="number", format="float"),
     *             @OA\Property(property="planned_start_date", type="string", format="date"),
     *             @OA\Property(property="notes", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Производственная партия создана",
     *         @OA\JsonContent(ref="#/components/schemas/ProductionBatch")
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Неверный ввод"
     *     )
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'quantity' => 'required|numeric|min:0.001',
            'planned_start_date' => 'nullable|date',
            'notes' => 'nullable|string'
        ]);

        try {
            $batch = $this->productionService->createProductionBatch(
                $validated['quantity'],
                $validated['planned_start_date'] ? Carbon::parse($validated['planned_start_date']) : null,
                $validated['notes']
            );

            return response()->json([
                'data' => $batch,
                'message' => 'Производственная партия создана'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 400);
        }
    }
    /**
     * @OA\Get(
     *     path="/production/create/{recipe}",
     *     operationId="createProductionBatch",
     *     tags={"Production"},
     *     summary="Создание производственной партии для рецепта",
     *     @OA\Parameter(
     *         name="recipe",
     *         in="path",
     *         required=true,
     *         description="ID рецепта",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Страница для создания производственной партии",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="recipe", type="object", ref="#/components/schemas/Recipe"),
     *             @OA\Property(property="currentStock", type="integer", description="Текущий остаток"),
     *             @OA\Property(property="estimatedCost", type="number", format="float", description="Оценочная стоимость")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Неверный запрос"
     *     )
     * )
     */
    public function create(Recipe $recipe)
    {
        return Inertia::render('Dashboard/Production/Create', [
            'recipe' => $recipe->load([
                'productVariant.product',
                'items.component.inventoryBalance',
                'outputUnit'
            ]),
            'currentStock' => $recipe->productVariant->getCurrentStock(),
            'estimatedCost' => $this->recipeService->calculateEstimatedCost($recipe)
        ]);
    }
    /**
     * @OA\Post(
     *     path="/production/batches/{batch}/start",
     *     operationId="startProductionBatch",
     *     tags={"Production"},
     *     summary="Запуск производственной партии",
     *     description="Метод для запуска производственной партии по ID.",
     *     @OA\Parameter(
     *         name="batch",
     *         in="path",
     *         required=true,
     *         description="ID производственной партии",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Производственная партия успешно запущена",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Производственная партия запущена")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Ошибка запуска производственной партии",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Ошибка: Невозможно запустить партию")
     *         )
     *     )
     * )
     */
    public function start(ProductionBatch $batch)
    {
        try {
            $this->productionService->startProduction($batch);

            return redirect()->back()
                ->with('success', 'Производственная партия запущена');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }


}
