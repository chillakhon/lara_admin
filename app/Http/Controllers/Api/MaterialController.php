<?php

namespace App\Http\Controllers\Api;

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

    public function index()
    {
        $materials = Material::with(['unit', 'inventoryBalance'])
            ->simplePaginate(20);
        return response()->json($materials);
    }

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

    public function show(Material $material)
    {
        return response()->json($material);
    }

    public function update(Request $request, Material $material)
    {
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'unit_id' => 'sometimes|required|exists:units,id',
        ]);

        $updatedMaterial = $this->materialService->updateMaterial($material, $validated);

        return response()->json(['message' => 'Material updated successfully', 'material' => $updatedMaterial]);
    }

    public function destroy(Material $material)
    {
        if ($material->inventoryBalance && $material->inventoryBalance->total_quantity > 0) {
            return response()->json(['error' => 'Cannot delete material with existing inventory'], Response::HTTP_BAD_REQUEST);
        }

        $this->materialService->deleteMaterial($material);

        return response()->json(['message' => 'Material deleted successfully']);
    }
}
