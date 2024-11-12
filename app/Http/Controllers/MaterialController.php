<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\Unit;
use App\Services\MaterialService;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Inertia\Inertia;

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
            ->paginate(20);
        $units = Unit::all();

        return Inertia::render('Dashboard/Materials/Index', [
            'materials' => $materials,
            'units' => $units,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'unit_id' => 'required|exists:units,id',
        ]);

        $material = $this->materialService->createMaterial($validated);

        return redirect()->route('dashboard.materials.index')->with('success', 'Material has been created.');
    }

    public function update(Request $request, Material $material)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'unit_id' => 'required|exists:units,id',
        ]);

        $this->materialService->updateMaterial($material, $validated);

        return redirect()->back()->with('success', 'Материал успешно обновлен.');
    }

    public function destroy(Material $material)
    {
        // Проверяем, есть ли связанные запасы
        if ($material->inventoryBalance && $material->inventoryBalance->total_quantity > 0) {
            return redirect()->back()->with('error', 'Невозможно удалить материал с существующим запасом.');
        }

        $this->materialService->deleteMaterial($material);

        return redirect()->back()->with('success', 'Материал успешно удален.');
    }

    public function addStock(Request $request, Material $material)
    {
        $validated = $request->validate([
            'quantity' => 'required|numeric|min:0',
            'price_per_unit' => 'required|numeric|min:0',
            'received_date' => 'required|date',
            'description' => 'nullable|string',
        ]);

        $this->inventoryService->addStock(
            'material',
            $material->id,
            $validated['quantity'],
            $validated['price_per_unit'],
            $material->unit_id,
            $validated['received_date'],
            auth()->id(),
            $validated['description'] ?? null
        );

        return redirect()->back()->with('success', 'Запас успешно добавлен.');
    }

    public function removeStock(Request $request, Material $material)
    {
        $validated = $request->validate([
            'quantity' => 'required|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        try {
            $this->inventoryService->removeStock(
                'material',
                $material->id,
                $validated['quantity'],
                auth()->id(),
                $validated['description'] ?? null
            );
            return redirect()->back()->with('success', 'Запас успешно списан.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
