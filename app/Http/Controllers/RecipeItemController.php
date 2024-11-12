<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRecipeItemRequest;
use App\Http\Requests\UpdateRecipeItemRequest;
use App\Models\RecipeItem;

class RecipeItemController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRecipeItemRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(RecipeItem $recipeItem)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(RecipeItem $recipeItem)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRecipeItemRequest $request, RecipeItem $recipeItem)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(RecipeItem $recipeItem)
    {
        //
    }
}
