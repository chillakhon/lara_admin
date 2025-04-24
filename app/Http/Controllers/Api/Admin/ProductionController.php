<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductionBatch;
use App\Models\ProductionBatchMaterial;
use App\Models\ProductionBatchOutputProduct;
use App\Models\Recipe;
use App\Models\User;
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
    public function index(Request $request): JsonResponse
    {
        $batches = $this->get_batches($request);

        return response()->json($batches);
    }


    protected function get_batches(Request $request)
    {
        $batches = ProductionBatch
            ::selectRaw("SUBSTRING_INDEX(batch_number, '-', 2) as base_batch_number")
            ->groupBy('base_batch_number');

        if ($request->get('batch_number')) {
            $batches->where('batch_number', 'like', "%{$request->get('batch_number')}%");
        }

        $perPage = $request->get('per_page');
        $with_batches = $request->boolean('with_batches', false);

        $transform = function ($group) use ($with_batches) {
            $baseBatch = $group->base_batch_number;

            $groupedBatches = ProductionBatch
                ::where('batch_number', 'like', "$baseBatch-%")
                ->orWhere('batch_number', '=', $baseBatch)
                ->select([
                    'id',
                    'batch_number',
                    'recipe_id',
                    'planned_quantity',
                    'status',
                    'planned_start_date',
                    'planned_end_date',
                    'started_at',
                    'completed_at',
                    'performer_id',
                    'created_by',
                    'completed_by',
                    'notes'
                ])->with([
                        'material_items' => function ($query) {
                            $query->select([
                                'production_batch_id',
                                'material_type',
                                'material_id',
                                'qty as quantity',
                            ])->with('material');
                        },
                        'output_products' => function ($query) {
                            $query->select([
                                'production_batch_id',
                                'output_type',
                                'output_id',
                                'qty',
                            ])->with('output');
                        },
                        'recipe'
                    ]);

            $groupedBatches = $groupedBatches->get();

            [
                $min_id,
                $planned_quantity,
                $grouped_batches_ids,
                $groupStatus,
                $notes,
                $planned_start_datetime,
                $planned_end_datetime,
                $started_datetime,
                $completed_datetime,
                $performers,
            ] = $this->get_information_from_groupe_batches($groupedBatches);

            $result_of_batch = [
                'base_batch_number' => $baseBatch,
                'planned_quantity' => $planned_quantity,
                'status' => $groupStatus,
                "notes" => $notes,
                "planned_start_datetime" => $planned_start_datetime,
                "planned_end_datetime" => $planned_end_datetime,
                "started_datetime" => $started_datetime,
                "completed_datetime" => $completed_datetime,
                "performers" => $performers,
                // 'materials' => $materials,
                // 'output_products' => $output_products,
            ];

            if ($with_batches) {
                $result_of_batch["batches"] = $groupedBatches;
            }

            return $result_of_batch;
        };

        if ($perPage) {
            $paginated = $batches->paginate($perPage);
            $transformed = $paginated->through($transform);

            return $transformed;
        } else {
            $batches = $batches->get()->map($transform);
            return $batches;
        }
    }

    protected function get_information_from_groupe_batches($groupedBatche): array
    {
        $min_id = $groupedBatche->min('id');
        $planned_quantity = $groupedBatche->sum('planned_quantity');
        $planned_start_datetime = $groupedBatche->min('planned_start_date');
        $planned_end_datetime = $groupedBatche->max('planned_end_date');
        $started_datetime = $groupedBatche->min('started_at');
        $completed_datetime = $groupedBatche->min('completed_at');
        $performer_id = $groupedBatche->pluck('performer_id');
        $performers = [];
        if (count($performer_id) >= 1) {
            $performers = User::whereIn('id', $performer_id)->get();
        }


        $grouped_batches_ids = $groupedBatche->pluck('id');
        $statuses = $groupedBatche->pluck('status')->unique();
        $groupStatus = '';
        $notes = $groupedBatche->pluck('notes')->first();

        if ($statuses->contains('in_progress')) {
            $groupStatus = 'in_progress';
        } elseif ($statuses->contains('pending')) {
            $groupStatus = 'pending';
        } elseif ($statuses->count() === 1 && $statuses->first() === 'completed') {
            $groupStatus = 'completed';
        } else {
            $groupStatus = 'mixed'; // fallback if statuses are inconsistent
        }

        return [
            $min_id,
            $planned_quantity,
            $grouped_batches_ids,
            $groupStatus,
            $notes,
            $planned_start_datetime,
            $planned_end_datetime,
            $started_datetime,
            $completed_datetime,
            $performers,
        ];
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
    public function store(Request $request)
    {
        $validated = $request->validate([
            'organization' => 'nullable|string',
            // 'quantity' => 'required|numeric|min:0.001',
            'planned_start_date' => 'nullable|date',
            'notes' => 'nullable|string',
            // 'tech_card_id' => 'required|integer|exists:recipes,id', // should exist in recipes table
            'batches' => 'required|array|min:1', // Products to be decremented
            // 'material_items.*.component_type' => 'nullable|string',
            // 'material_items.*.component_id' => 'nullable|integer', // should exist in products table
            // 'material_items.*.quantity' => 'required|numeric|min:0.001',
            // 'output_products' => 'required|array|min:1', // Products to be incremented
            // 'output_products.*.tech_card_id' => 'required|integer|exists:recipes,id', // should exist in recipes table
            // 'output_products.*.qty' => 'required|numeric|min:0.001',
            // 'output_products.*.product_id' => 'nullable|integer',
            // 'output_products.*.product_variant_id' => 'nullable|integer',
        ]);

        $materials = $this->get_materials_for_production_batch($validated['batches']);

        [$canProduce, $material_qtyies] = $this
            ->productionService
            ->validateProductionPossibility($materials);

        // uncomment this if you want to check if there are enough materials (is primary)
        // if (!$canProduce) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Недостаточно материалов для производства',
        //     ]);
        // }

        $batch = $this->productionService->createProductionBatch(
            // $validated['tech_card_id'],
            $validated['batches'],
            $validated['planned_start_date'] ? Carbon::parse($validated['planned_start_date']) : null,
            $validated['notes']
        );

        return response()->json([
            'data' => $batch,
            'message' => 'Производственная партия создана'
        ], 201);

    }

    private function get_materials_for_production_batch($recipes)
    {
        $materials = [];

        foreach ($recipes as $recipe) {
            foreach ($recipe['material_items'] as $item) {
                $key = $item['component_type'] . '_' . $item['component_id'];

                if (!isset($materials[$key])) {
                    $materials[$key] = [
                        'component_type' => $item['component_type'],
                        'component_id' => $item['component_id'],
                        'quantity' => $item['quantity'],
                    ];
                } else {
                    $materials[$key]['quantity'] += $item['quantity'];
                }
            }
        }

        $materials = array_values($materials);

        return $materials;
    }



    public function update(Request $request)
    {
        $validated = $request->validate([
            "base_batch_number" => 'required|string',
            'organization' => 'nullable|string',
            'planned_start_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'batches' => 'required|array|min:1', // Products to be decremented
        ]);


        ProductionBatch
            ::where('batch_number', 'like', "%{$validated['base_batch_number']}%")
            ->update([
                'planned_start_date' => $validated['planned_start_date'],
                'notes' => $validated['notes'],
            ]);

        // ids that are already in the database
        // will delete all other batches which are not inside this array
        $batch_ids = [];

        foreach ($validated['batches'] as $key => $batch) {
            $find_batch = ProductionBatch::where('id', $batch['id'])
                ->where('batch_number', $batch['batch_number'])
                ->first();

            $batchData = [
                // 'batch_number' => $batch_number . '-' . $index + 1,
                // 'recipe_id' => $batchItem['recipe_id'],
                'planned_quantity' => $batch['planned_qty'],
                // 'status' => 'pending',
                'performer_id' => $batch['performer_id'],
                'planned_start_date' => $validated['planned_start_date'] ?? now(),
                // 'planned_end_date' => null, // $this->calculatePlannedEndDate($plannedStartDate, $recipe->production_time),
                // 'created_by' => auth()->id(),
                'notes' => $validated['notes']
            ];

            if ($find_batch) {
                $batch_ids[] = $find_batch->id;
                $find_batch->update($batchData);
                
            } else {

            }
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
