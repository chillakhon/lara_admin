<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\Unit;
use App\Services\InventoryService;
use App\Services\MaterialService;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * @OA\Tag(
 *     name="Materials",
 *     description="API для управления материалами"
 * )
 *
 * @OA\Components(
 *     @OA\Schema(
 *         schema="Material",
 *         title="Material",
 *         description="Схема материала",
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(property="title", type="string", example="Бетон"),
 *         @OA\Property(property="unit_id", type="integer", example=2),
 *         @OA\Property(property="created_at", type="string", format="date-time", example="2024-03-01T12:00:00Z"),
 *         @OA\Property(property="updated_at", type="string", format="date-time", example="2024-03-02T15:30:00Z")
 *     )
 * )
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
     * Получить список материалов
     *
     * @OA\Get(
     *     path="/api/materials",
     *     tags={"Materials"},
     *     summary="Получить список материалов",
     *     @OA\Response(
     *         response=200,
     *         description="Список материалов",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Material"))
     *     )
     * )
     */
    public function index()
    {
        $materials = Material::with(['unit', 'inventoryBalance'])->paginate(20);
        $units = Unit::all();

        return Inertia::render('Dashboard/Materials/Index', [
            'materials' => $materials,
            'units' => $units,
        ]);
    }

    /**
     * Создать новый материал
     *
     * @OA\Post(
     *     path="/api/materials",
     *     tags={"Materials"},
     *     summary="Создать новый материал",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"title", "unit_id"},
     *             @OA\Property(property="title", type="string", example="Бетон"),
     *             @OA\Property(property="unit_id", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Материал создан"),
     *     @OA\Response(response=422, description="Ошибка валидации")
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'unit_id' => 'required|exists:units,id',
        ]);

        $this->materialService->createMaterial($validated);

        return redirect()->route('dashboard.materials.index')->with('success', 'Material has been created.');
    }

    /**
     * Удалить материал
     *
     * @OA\Delete(
     *     path="/api/materials/{id}",
     *     tags={"Materials"},
     *     summary="Удалить материал",
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Материал удален"),
     *     @OA\Response(response=400, description="Ошибка: материал нельзя удалить")
     * )
     */
    public function destroy(Material $material)
    {
        if ($material->inventoryBalance && $material->inventoryBalance->total_quantity > 0) {
            return redirect()->back()->with('error', 'Невозможно удалить материал с существующим запасом.');
        }

        $this->materialService->deleteMaterial($material);

        return redirect()->back()->with('success', 'Материал успешно удален.');
    }
}
