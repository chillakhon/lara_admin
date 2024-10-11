<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Providers\AppServiceProvider;
use App\Services\MaterialService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MaterialController extends Controller
{
    protected $materialService;

    public function __construct(MaterialService $materialService)
    {
        $this->materialService = $materialService;
    }

    public function index()
    {
        $materials = Material::with('conversions')->paginate(20);
        //$materials = AppServiceProvider::setUrlsToHttps($materials);
        return Inertia::render('Dashboard/Materials/Index', [
            'materials' => $materials
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'unit_of_measurement' => 'required|string|max:50',
            'cost_per_unit' => 'required|numeric|min:0',
            'conversion.from_unit' => 'sometimes|required|string|max:50',
            'conversion.to_unit' => 'sometimes|required|string|max:50',
            'conversion.conversion_factor' => 'sometimes|required|numeric|min:0',
        ]);

        $material = $this->materialService->createMaterial($validated);

        return redirect()->back()->with('success', 'Material created successfully.');
    }

    public function update(Request $request, Material $material)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'unit_of_measurement' => 'required|string|max:50',
            'cost_per_unit' => 'required|numeric|min:0',
        ]);

        $material->update($validated);

        return redirect()->back()->with('success', 'Material updated successfully.');
    }

    public function destroy(Material $material)
    {
        $material->delete();

        return redirect()->back()->with('success', 'Material deleted successfully.');
    }
}
