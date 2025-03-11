<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductionBatch;
use App\Models\Recipe;
use App\Services\ProductionService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;


class ProductionController extends Controller
{
    protected $productionService;

    public function __construct(ProductionService $productionService)
    {
        $this->productionService = $productionService;
    }

    /**
     * @OA\Get(
     *     path="/api/production",
     *     summary="Получить список партий производства",
     *     tags={"Production"},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Номер страницы для пагинации",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Список партий производства",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="recipe", type="object",
     *                         @OA\Property(property="productVariant", type="object",
     *                             @OA\Property(property="product", type="string", example="Product Name")
     *                         ),
     *                         @OA\Property(property="outputUnit", type="string", example="kg")
     *                     ),
     *                     @OA\Property(property="createdBy", type="object",
     *                         @OA\Property(property="id", type="integer", example=2),
     *                         @OA\Property(property="name", type="string", example="John Doe")
     *                     ),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2025-03-11T12:00:00Z")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function index(): JsonResponse
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
     * @OA\Get(
     *     path="/api/production/create/{recipe}",
     *     summary="Получение данных для создания производственной партии",
     *     tags={"Production"},
     *     @OA\Parameter(
     *         name="recipe",
     *         in="path",
     *         required=true,
     *         description="ID рецепта",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Данные о рецепте для производства",
     *         @OA\JsonContent(
     *             @OA\Property(property="recipe", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="productVariant", type="object",
     *                     @OA\Property(property="id", type="integer", example=10),
     *                     @OA\Property(property="name", type="string", example="Товар X")
     *                 ),
     *                 @OA\Property(property="items", type="array",
     *                     @OA\Items(type="object",
     *                         @OA\Property(property="id", type="integer", example=5),
     *                         @OA\Property(property="component", type="string", example="Сырье A"),
     *                         @OA\Property(property="inventoryBalance", type="integer", example=100)
     *                     )
     *                 )
     *             ),
     *             @OA\Property(property="currentStock", type="integer", example=50),
     *             @OA\Property(property="estimatedCost", type="number", format="float", example=1500.50)
     *         )
     *     )
     * )
     */
    public function create(Recipe $recipe)
    {
        return response()->json([
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
     *     path="/api/batches",
     *     summary="Создать новую производственную партию",
     *     tags={"Production"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"quantity"},
     *             @OA\Property(property="quantity", type="number", format="float", example=10.5),
     *             @OA\Property(property="planned_start_date", type="string", format="date", example="2025-04-01"),
     *             @OA\Property(property="notes", type="string", example="Дополнительные заметки")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Производственная партия успешно создана",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="quantity", type="number", format="float", example=10.5),
     *                 @OA\Property(property="planned_start_date", type="string", format="date", example="2025-04-01"),
     *                 @OA\Property(property="notes", type="string", example="Дополнительные заметки"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-03-11T12:00:00Z")
     *             ),
     *             @OA\Property(property="message", type="string", example="Производственная партия создана")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Ошибка валидации или другая ошибка",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Ошибка создания партии")
     *         )
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
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
     * @OA\Post(
     *     path="/api/batches/{batch}/start",
     *     summary="Запуск производственной партии",
     *     tags={"Production"},
     *     @OA\Parameter(
     *         name="batch",
     *         in="path",
     *         required=true,
     *         description="ID производственной партии",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Производственная партия успешно запущена",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Производственная партия запущена")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Ошибка запуска",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Произошла ошибка при запуске")
     *         )
     *     )
     * )
     */
    public function start(ProductionBatch $batch): JsonResponse
    {
        try {
            $this->productionService->startProduction($batch);

            return response()->json([
                'message' => 'Производственная партия запущена'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/production/batches/{batch}/complete",
     *     summary="Завершение производственной партии",
     *     tags={"Production"},
     *     @OA\Parameter(
     *         name="batch",
     *         in="path",
     *         required=true,
     *         description="ID производственной партии",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="actual_quantity", type="number", format="float", example=100.5),
     *             @OA\Property(property="notes", type="string", nullable=true, example="Партия завершена успешно")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Партия завершена",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Производственная партия успешно завершена"),
     *             @OA\Property(property="batch", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="status", type="string", example="completed"),
     *                 @OA\Property(property="actual_quantity", type="number", format="float", example=100.5)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Ошибка завершения",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Недостаточно сырья")
     *         )
     *     )
     * )
     */
    public function complete(ProductionBatch $batch, Request $request)
    {
        try {
            $validated = $request->validate([
                'actual_quantity' => 'required|numeric|min:0',
                'notes' => 'nullable|string'
            ]);

            $this->productionService->completeProduction(
                $batch,
                $validated['actual_quantity'],
                $validated['notes'] ?? null
            );

            return response()->json([
                'message' => 'Производственная партия успешно завершена',
                'batch' => [
                    'id' => $batch->id,
                    'status' => 'completed',
                    'actual_quantity' => $validated['actual_quantity']
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/production/batches/{batch}/cancel",
     *     summary="Отмена производственной партии",
     *     tags={"Production"},
     *     @OA\Parameter(
     *         name="batch",
     *         in="path",
     *         required=true,
     *         description="ID производственной партии",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="reason", type="string", maxLength=1000, example="Нет необходимых компонентов")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Партия отменена",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Производственная партия отменена"),
     *             @OA\Property(property="batch", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="status", type="string", example="cancelled"),
     *                 @OA\Property(property="reason", type="string", example="Нет необходимых компонентов")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Ошибка отмены",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Партия уже завершена и не может быть отменена")
     *         )
     *     )
     * )
     */
    public function cancel(ProductionBatch $batch, Request $request)
    {
        try {
            $validated = $request->validate([
                'reason' => 'required|string|max:1000'
            ]);

            $this->productionService->cancelProduction($batch, $validated['reason']);

            return response()->json([
                'message' => 'Производственная партия отменена',
                'batch' => [
                    'id' => $batch->id,
                    'status' => 'cancelled',
                    'reason' => $validated['reason']
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 400);
        }
    }

}
