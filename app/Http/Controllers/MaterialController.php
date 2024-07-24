<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMaterialRequest;
use App\Http\Requests\UpdateMaterialRequest;
use App\Models\Material;
use App\Models\RawMaterial;
use App\Services\MaterialPriceCalculator;
use Inertia\Inertia;

class MaterialController extends Controller
{
    private $calculator;

    public function __construct(MaterialPriceCalculator $calculator)
    {
        $this->calculator = $calculator;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $materials = Material::all();
        return Inertia::render('Materials/Index', ['materials' => $materials]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $rawMaterials = RawMaterial::all();
        return Inertia::render('Materials/Create', ['rawMaterials' => $rawMaterials]);
    }

    /**
     * Store a newly created resource in storage.
     */

    public function store(StoreMaterialRequest $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'nullable|numeric|min:0',
            'unit' => 'required|string|max:50',
            'is_calculated' => 'required|boolean',
            'formula' => 'required_if:is_calculated,true|nullable|string',
            'from_unit' => 'required_if:is_calculated,true|nullable|string',
            'to_unit' => 'required_if:is_calculated,true|nullable|string',
        ]);

        if ($validated['is_calculated']) {
            $validated['price'] = $this->calculator->calculate(
                $validated['formula'],
                $validated['from_unit'],
                $validated['to_unit']
            );
        }

        Material::create($validated);

        return redirect()->route('materials.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(Material $material)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Material $material)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMaterialRequest $request, Material $material)
    {
        //
    }

    public function updatePrices()
    {
        $calculatedMaterials = Material::where('is_calculated', true)->get();

        foreach ($calculatedMaterials as $material) {
            $material->price = $this->calculator->calculate($material->formula, $material->conversion_factor);
            $material->save();
        }

        return response()->json(['message' => 'Material prices updated successfully']);
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Material $material)
    {
        //
    }
}
