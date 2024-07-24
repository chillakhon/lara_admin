<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRawMaterialsRequest;
use App\Http\Requests\UpdateRawMaterialsRequest;
use App\Models\RawMaterial;
use Inertia\Inertia;

class RawMaterialsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $rawMaterials = RawMaterial::all();
        return Inertia::render('Dashboard/RawMaterials', ['rawMaterials' => $rawMaterials]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('RawMaterials/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRawMaterialsRequest $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'unit' => 'required|string|max:50',
        ]);

        RawMaterial::create($validated);

        return redirect()->route('dashboard.raw');
    }

    /**
     * Display the specified resource.
     */
    public function show(RawMaterial $rawMaterials)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(RawMaterial $rawMaterials)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRawMaterialsRequest $request, RawMaterial $rawMaterials)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(RawMaterial $rawMaterials)
    {
        //
    }
}
