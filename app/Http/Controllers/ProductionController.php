<?php

namespace App\Http\Controllers;

use App\Models\ProductionBatch;
use App\Models\Recipe;
use App\Services\ProductionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProductionController extends Controller
{
    protected $productionService;

    public function __construct(ProductionService $productionService)
    {
        $this->productionService = $productionService;
    }

    public function index()
    {
        $batches = ProductionBatch::with([
            'recipe.productVariant.product',
            'recipe.outputUnit',
            'createdBy'
        ])
            ->latest()
            ->paginate(20);

        return Inertia::render('Dashboard/Production/Index', [
            'batches' => $batches
        ]);
    }

    public function create(Recipe $recipe)
    {
        return Inertia::render('Dashboard/Production/Create', [
            'recipe' => $recipe->load([
                'productVariant.product',
                'items.component.inventoryBalance',
                'outputUnit'
            ]),
            'currentStock' => $recipe->productVariant->getCurrentStock(),
            'estimatedCost' => $this->recipeService->calculateEstimatedCost($recipe)
        ]);
    }

    public function store(Request $request, Recipe $recipe)
    {
        $validated = $request->validate([
            'quantity' => 'required|numeric|min:0.001',
            'planned_start_date' => 'nullable|date',
            'notes' => 'nullable|string'
        ]);

        try {
            $batch = $this->productionService->createProductionBatch(
                $recipe,
                $validated['quantity'],
                $validated['planned_start_date'] ? Carbon::parse($validated['planned_start_date']) : null,
                $validated['notes']
            );

            return redirect()->route('dashboard.production.show', $batch)
                ->with('success', 'Производственная партия создана');
        } catch (\Exception $e) {
            return back()->withErrors(['quantity' => $e->getMessage()]);
        }
    }

    // Остальные методы...
}
