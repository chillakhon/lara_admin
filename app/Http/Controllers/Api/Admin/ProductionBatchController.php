<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductionBatch;
use App\Models\Recipe;
use App\Services\ProductionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Carbon\Carbon;

class ProductionBatchController extends Controller
{
    protected $productionService;

    public function __construct(ProductionService $productionService)
    {
        $this->productionService = $productionService;
    }

    /**
     * @OA\Get(
     *     path="/api/admin/production/batches",
     *     summary="Получить список производственных партий",
     *     description="Возвращает список производственных партий, ожидающих обработки.",
     *     @OA\Response(
     *         response=200,
     *         description="Список производственных партий",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/ProductionBatch")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Ошибка сервера"
     *     )
     * )
     */
    public function index()
    {
        $batches = $this->productionService->getPendingProductions();

        // Возвращаем JSON-ответ с данными производственных партий
        return response()->json([
            'batches' => $batches
        ]);
    }


    /**
     * @OA\Post(
     *     path="/api/admin/production/batches",
     *     summary="Создание новой производственной партии",
     *     description="Создает новую производственную партию на основе рецепта.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"recipe_id", "quantity", "planned_start_date"},
     *             @OA\Property(property="recipe_id", type="integer", description="ID рецепта"),
     *             @OA\Property(property="quantity", type="number", format="float", description="Количество продукции"),
     *             @OA\Property(property="planned_start_date", type="string", format="date", description="Запланированная дата начала производства"),
     *             @OA\Property(property="notes", type="string", description="Дополнительные примечания")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Производственная партия успешно создана",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ProductionBatch"
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Неверные данные",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", description="Ошибка валидации или других данных")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Ошибка сервера",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", description="Ошибка на сервере")
     *         )
     *     )
     * )
     */


    /**
     * @OA\Get(
     *     path="/api/admin/production/batches/{batch}",
     *     summary="Получение информации о производственной партии",
     *     description="Возвращает информацию о производственной партии, включая доступность компонентов.",
     *     @OA\Parameter(
     *         name="batch",
     *         in="path",
     *         required=true,
     *         description="ID производственной партии",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Информация о производственной партии",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="batch", ref="#/components/schemas/ProductionBatch"),
     *             @OA\Property(property="componentsAvailability", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Партия не найдена",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", description="Ошибка")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Ошибка сервера",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", description="Ошибка на сервере")
     *         )
     *     )
     * )
     */


    /**
     * @OA\Post(
     *     path="/api/admin/production/batches/{batch}/start",
     *     summary="Начать производство для партии",
     *     description="Запускает производство для указанной производственной партии.",
     *     @OA\Parameter(
     *         name="batch",
     *         in="path",
     *         required=true,
     *         description="ID производственной партии",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Производство успешно начато",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Производство начато")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Ошибка начала производства",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", description="Ошибка")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Производственная партия не найдена",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", description="Партия не найдена")
     *         )
     *     )
     * )
     */

    public function complete(Request $request, ProductionBatch $batch)
    {
        $validated = $request->validate([
            'actual_quantity' => 'required|numeric|min:0.001',
            'notes' => 'nullable|string'
        ]);

        try {
            $this->productionService->completeProduction(
                $batch,
                $validated['actual_quantity'],
                $validated['notes']
            );

            return redirect()->back()->with('success', 'Производство успешно завершено');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/api/admin/production/batches/{batch}/cancel",
     *     summary="Отменить производство для партии",
     *     description="Отменяет производство для указанной производственной партии.",
     *     @OA\Parameter(
     *         name="batch",
     *         in="path",
     *         required=true,
     *         description="ID производственной партии",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="reason", type="string", description="Причина отмены")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Производство успешно отменено",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Производственная партия отменена")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Ошибка отмены производства",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", description="Ошибка")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Производственная партия не найдена",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", description="Партия не найдена")
     *         )
     *     )
     * )
     */
    public function cancel(Request $request, ProductionBatch $batch)
    {
        $validated = $request->validate([
            'reason' => 'required|string'
        ]);

        try {
            // Выполняем отмену производства
            $this->productionService->cancelProduction($batch, $validated['reason']);

            // Возвращаем успешный ответ в формате JSON
            return response()->json([
                'message' => 'Производственная партия отменена'
            ], 200);

        } catch (\Exception $e) {
            // В случае ошибки возвращаем сообщение об ошибке в формате JSON
            return response()->json([
                'error' => 'Произошла ошибка при отмене производства: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Оценить компоненты и время производства
     *
     * Оценка доступности компонентов и времени, необходимого для производства на основе рецепта и количества.
     *
     * @OA\Post(
     *     path="/api/production/estimate",
     *     tags={"Production"},
     *     summary="Оценить доступность компонентов и время производства",
     *     description="Данный метод позволяет оценить доступность компонентов для производства и время его выполнения.",
     *     @OA\RequestBody(
     *         required=true,
     *         description="Данные для оценки",
     *         @OA\JsonContent(
     *             required={"recipe_id", "quantity"},
     *             @OA\Property(property="recipe_id", type="integer", description="ID рецепта"),
     *             @OA\Property(property="quantity", type="number", format="float", description="Количество продукции")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Успешный ответ с оценкой доступности компонентов и времени",
     *         @OA\JsonContent(
     *             @OA\Property(property="availability", type="object", description="Информация о доступности компонентов"),
     *             @OA\Property(property="timing", type="object", description="Оценка времени производства")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Ошибка валидации данных",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", description="Описание ошибки")
     *         )
     *     ),
     * )
     */

    public function estimateProduction(Request $request)
    {
        $validated = $request->validate([
            'recipe_id' => 'required|exists:recipes,id',
            'quantity' => 'required|numeric|min:0.001'
        ]);

        try {
            $recipe = Recipe::findOrFail($validated['recipe_id']);

            // Оценка доступности компонентов
            $availability = $this->productionService->checkComponentsAvailability(
                $recipe,
                $validated['quantity']
            );

            // Оценка времени производства
            $timing = $this->productionService->estimateProductionTime(
                $recipe,
                $validated['quantity']
            );

            // Возврат JSON-ответа с данными
            return response()->json([
                'availability' => $availability,
                'timing' => $timing
            ], 200); // Указываем код ответа 200 для успешного запроса

        } catch (\Exception $e) {
            // В случае ошибки возвращаем сообщение об ошибке
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

}
