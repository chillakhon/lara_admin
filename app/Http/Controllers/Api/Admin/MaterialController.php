<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Services\InventoryService;
use App\Services\MaterialService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

/**
 * @OA\Tag(name="Materials", description="API для управления материалами")
 */
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
     *     path="/api/admin/materials",
     *     summary="Получение списка материалов",
     *     tags={"Materials"},
     *     @OA\Response(response=200, description="Успешный ответ")
     * )
     */
    public function index()
    {
        $materials = Material::with(['unit', 'inventoryBalance'])->simplePaginate(20);
        return response()->json($materials);
    }

    /**
     * @OA\Post(
     *     path="/api/admin/materials",
     *     summary="Создание материала",
     *     tags={"Materials"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"title", "unit_id"},
     *             @OA\Property(property="title", type="string", example="Дерево"),
     *             @OA\Property(property="unit_id", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Материал создан")
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
     *     path="/api/admin/materials/{id}",
     *     summary="Получение информации о материале",
     *     tags={"Materials"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Успешный ответ")
     * )
     */
    public function show(Material $material)
    {
        return response()->json($material);
    }

    /**
     * @OA\Put(
     *     path="/api/admin/materials/{id}",
     *     summary="Обновление материала",
     *     tags={"Materials"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="title", type="string", example="Металл"),
     *             @OA\Property(property="unit_id", type="integer", example=2)
     *         )
     *     ),
     *     @OA\Response(response=200, description="Материал обновлен")
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
     *     path="/api/admin/materials/{id}",
     *     summary="Удаление материала",
     *     tags={"Materials"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Материал удален"),
     *     @OA\Response(response=400, description="Ошибка: Невозможно удалить материал с остатками на складе")
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
