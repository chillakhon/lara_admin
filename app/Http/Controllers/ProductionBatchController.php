<?php

namespace App\Http\Controllers;

use App\Models\ProductionBatch;
use App\Models\Recipe;
use App\Services\ProductionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Carbon\Carbon;

class ProductionBatchController extends Controller
{
    protected $productionService;

    public function __construct(ProductionService $productionService)
    {
        $this->productionService = $productionService;
    }

    public function index()
    {
        $batches = $this->productionService->getPendingProductions();

        return Inertia::render('Dashboard/Production/Index', [
            'batches' => $batches
        ]);
    }

    public function store(Request $request)
    {
        static $executionCount = 0;
        $executionCount++;
        Log::info('======= START PRODUCTION BATCH CREATION =======', [
            'execution_count' => $executionCount,
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'middleware' => $request->route()->middleware(),
            'controller' => get_class($this) . '@' . __FUNCTION__,
        ]);

        $validated = $request->validate([
            'recipe_id' => 'required|exists:recipes,id',
            'quantity' => 'required|numeric|min:0.001',
            'planned_start_date' => 'required|date',
            'notes' => 'nullable|string'
        ]);

        try {
            Log::info('Validated data:', $validated);
            $recipe = Recipe::findOrFail($validated['recipe_id']);
            Log::info('Recipe loaded', [
                'recipe' => $recipe->toArray()
            ]);
            // Проверяем доступность компонентов перед созданием партии
            $availability = $this->productionService->checkComponentsAvailability(
                $recipe,
                $validated['quantity']
            );
            Log::info('Components availability checked', [
                'availability' => $availability
            ]);
            if (!$availability['can_produce']) {
                Log::warning('Insufficient components', [
                    'availability' => $availability
                ]);
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Недостаточно компонентов для производства');
            }

            $plannedStartDate = Carbon::parse($validated['planned_start_date']);
            Log::info('Creating production batch', [
                'recipe_id' => $recipe->id,
                'quantity' => $validated['quantity'],
                'planned_start_date' => $plannedStartDate,
                'notes' => $validated['notes'] ?? null
            ]);
            // Создаем производственную партию
            try {
                // Создаем производственную партию
                $batch = $this->productionService->createProductionBatch(
                    $recipe,
                    $validated['quantity'],
                    $plannedStartDate,
                    $validated['notes'] ?? null
                );

                Log::info('Production batch created successfully', [
                    'batch_id' => $batch->id,
                    'batch_number' => $batch->batch_number
                ]);

                return redirect()->route('dashboard.production.index', $batch)
                    ->with('success', 'Производственная партия успешно создана');

            } catch (\Exception $e) {
                Log::error('Error in createProductionBatch', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                throw $e;
            }

        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    public function show(ProductionBatch $batch)
    {
        $batch->load([
            'recipe.items.component',
            'recipe.outputUnit',
            'materialConsumptions.component',
            'createdBy',
            'completedBy'
        ]);

        // Получаем актуальную информацию о доступности компонентов
        $availability = $this->productionService->checkComponentsAvailability(
            $batch->recipe,
            $batch->planned_quantity
        );

        return Inertia::render('Dashboard/Production/Show', [
            'batch' => $batch,
            'componentsAvailability' => $availability
        ]);
    }

    public function start(ProductionBatch $batch)
    {
        try {
            $this->productionService->startProduction($batch);
            return redirect()->back()->with('success', 'Производство начато');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function complete(Request $request, ProductionBatch $batch)
    {
        $validated = $request->validate([
            'actual_quantity' => 'required|numeric|min:0.001',
            'notes' => 'nullable|string'
        ]);

        try {
            $this->productionService->completeProduction(
                $batch,
                $validated['actual_quantity'],
                $validated['notes']
            );

            return redirect()->back()->with('success', 'Производство успешно завершено');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function cancel(Request $request, ProductionBatch $batch)
    {
        $validated = $request->validate([
            'reason' => 'required|string'
        ]);

        try {
            $this->productionService->cancelProduction($batch, $validated['reason']);
            return redirect()->back()->with('success', 'Производственная партия отменена');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function estimateProduction(Request $request)
    {
        $validated = $request->validate([
            'recipe_id' => 'required|exists:recipes,id',
            'quantity' => 'required|numeric|min:0.001'
        ]);

        try {
            $recipe = Recipe::findOrFail($validated['recipe_id']);

            $availability = $this->productionService->checkComponentsAvailability(
                $recipe,
                $validated['quantity']
            );

            $timing = $this->productionService->estimateProductionTime(
                $recipe,
                $validated['quantity']
            );

            return response()->json([
                'availability' => $availability,
                'timing' => $timing
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }
}
