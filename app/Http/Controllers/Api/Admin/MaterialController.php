<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Services\InventoryService;
use App\Services\MaterialService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class MaterialController extends Controller
{
    protected $materialService;
    protected $inventoryService;

    public function __construct(MaterialService $materialService, InventoryService $inventoryService)
    {
        $this->materialService = $materialService;
        $this->inventoryService = $inventoryService;
    }

    /**
     * @OA\Get(
     *     path="/api/materials",
     *     summary="Получить список материалов",
     *     description="Возвращает список материалов с их единицами измерения и балансом на складе",
     *     operationId="getMaterials",
     *     tags={"Materials"},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Номер страницы для пагинации",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Успешный ответ со списком материалов",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(ref="#/components/schemas/Material")
     *             ),
     *             @OA\Property(property="next_page_url", type="string", nullable=true, example="http://example.com/api/materials?page=2"),
     *             @OA\Property(property="prev_page_url", type="string", nullable=true, example=null)
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Ошибка сервера",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Server error")
     *         )
     *     )
     * )
     */
    public function index()
    {
        $materials = Material::with(['unit', 'inventoryBalance'])
            ->simplePaginate(20);
        return response()->json($materials);
    }

    /**
     * @OA\Post(
     *     path="/api/materials",
     *     summary="Создать новый материал",
     *     description="Создаёт новый материал и возвращает его данные",
     *     operationId="createMaterial",
     *     tags={"Materials"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"title", "unit_id"},
     *             @OA\Property(property="title", type="string", maxLength=255, example="Steel Pipe"),
     *             @OA\Property(property="unit_id", type="integer", format="int64", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Материал успешно создан",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Material created successfully"),
     *             @OA\Property(property="material", ref="#/components/schemas/Material")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Ошибка валидации",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="title", type="array",
     *                     @OA\Items(type="string", example="The title field is required.")
     *                 ),
     *                 @OA\Property(property="unit_id", type="array",
     *                     @OA\Items(type="string", example="The selected unit_id is invalid.")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'unit_id' => 'required|exists:units,id',
        ]);

        $material = $this->materialService->createMaterial($validated);

        return response()->json([
            'message' => 'Material created successfully',
            'material' => $material], ResponseAlias::HTTP_CREATED);
    }

    /**
     * @OA\Get(
     *     path="/api/materials/{material}",
     *     summary="Получить материал по ID",
     *     description="Возвращает данные конкретного материала",
     *     operationId="getMaterialById",
     *     tags={"Materials"},
     *     @OA\Parameter(
     *         name="material",
     *         in="path",
     *         description="ID материала",
     *         required=true,
     *         @OA\Schema(type="integer", format="int64", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Успешный ответ с данными материала",
     *         @OA\JsonContent(ref="#/components/schemas/Material")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Материал не найден",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Material not found")
     *         )
     *     )
     * )
     */
    public function show(Material $material)
    {
        return response()->json($material);
    }

    /**
     * @OA\Put(
     *     path="/api/materials/{material}",
     *     summary="Обновить материал",
     *     description="Обновляет информацию о материале по ID",
     *     operationId="updateMaterial",
     *     tags={"Materials"},
     *     @OA\Parameter(
     *         name="material",
     *         in="path",
     *         description="ID материала",
     *         required=true,
     *         @OA\Schema(type="integer", format="int64", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"title", "unit_id"},
     *             @OA\Property(property="title", type="string", maxLength=255, example="Steel Rod"),
     *             @OA\Property(property="unit_id", type="integer", format="int64", example=2)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Материал успешно обновлён",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Material updated successfully"),
     *             @OA\Property(property="material", ref="#/components/schemas/Material")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Ошибка валидации",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Материал не найден",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Material not found")
     *         )
     *     )
     * )
     */
    public function update(Request $request, Material $material)
    {
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'unit_id' => 'sometimes|required|exists:units,id',
        ]);

        $updatedMaterial = $this->materialService->updateMaterial($material, $validated);

        return response()->json(['message' => 'Material updated successfully', 'material' => $updatedMaterial]);
    }

    /**
     * @OA\Delete(
     *     path="/api/materials/{material}",
     *     summary="Удалить материал",
     *     description="Удаляет материал по ID, если у него нет остатков на складе",
     *     operationId="deleteMaterial",
     *     tags={"Materials"},
     *     @OA\Parameter(
     *         name="material",
     *         in="path",
     *         description="ID материала",
     *         required=true,
     *         @OA\Schema(type="integer", format="int64", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Материал успешно удалён",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Material deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Невозможно удалить материал с существующими остатками",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Cannot delete material with existing inventory")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Материал не найден",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Material not found")
     *         )
     *     )
     * )
     */
    public function destroy(Material $material)
    {
        if ($material->inventoryBalance && $material->inventoryBalance->total_quantity > 0) {
            return response()->json(['error' => 'Cannot delete material with existing inventory'], Response::HTTP_BAD_REQUEST);
        }

        $this->materialService->deleteMaterial($material);

        return response()->json(['message' => 'Material deleted successfully']);
    }
}
