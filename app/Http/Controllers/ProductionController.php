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

            return redirect()->route('dashboard.production.index', $batch)
                ->with('success', 'Производственная партия создана');
        } catch (\Exception $e) {
            return back()->withErrors(['quantity' => $e->getMessage()]);
        }
    }

    public function start(ProductionBatch $batch)
    {
        try {
            $this->productionService->startProduction($batch);
            
            return redirect()->back()
                ->with('success', 'Производственная партия запущена');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function complete(ProductionBatch $batch, Request $request)
    {
        try {
            $validated = $request->validate([
                'actual_quantity' => 'required|numeric|min:0',
                'notes' => 'nullable|string'
            ]);

            $this->productionService->completeProduction(
                $batch,
                $validated['actual_quantity'],
                $validated['notes'] ?? null
            );
            
            return redirect()->back()
                ->with('success', 'Производственная партия успешно завершена');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function cancel(ProductionBatch $batch, Request $request)
        {
            try {
                $validated = $request->validate([
                    'reason' => 'required|string|max:1000'
                ]);

                $this->productionService->cancelProduction($batch, $validated['reason']);
                
                return redirect()->back()
                    ->with('success', 'Производственная партия отменена');
            } catch (\Exception $e) {
                return redirect()->back()
                    ->withErrors(['error' => $e->getMessage()]);
            }
        }
}
