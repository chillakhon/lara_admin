<?php

namespace App\Services;

use App\Models\ProductionBatch;
use App\Models\Recipe;
use App\Models\ProductVariant;
use App\Exceptions\ProductionException;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProductionService
{
    protected $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    public function createProductionBatch(
        Recipe $recipe,
        float $quantity,
        ?Carbon $plannedStartDate = null,
        ?string $notes = null
    ): ProductionBatch {
        Log::info('Starting createProductionBatch in service', [
            'recipe_id' => $recipe->id,
            'quantity' => $quantity,
            'planned_start_date' => $plannedStartDate,
            'notes' => $notes
        ]);

        try {
            // Проверяем возможность производства
            $this->validateProductionPossibility($recipe, $quantity);

            Log::info('Production possibility validated');

            return DB::transaction(function () use ($recipe, $quantity, $plannedStartDate, $notes) {
                // Получаем вариант продукта
                $productVariant = $this->getProductVariant($recipe);

                Log::info('Product variant determined', [
                    'product_variant' => $productVariant ? $productVariant->toArray() : null
                ]);

                if (!$productVariant) {
                    throw new \Exception('Не найден вариант продукта для данного рецепта. Проверьте настройки рецепта и продукта.');
                }

                $batchData = [
                    'batch_number' => $this->generateBatchNumber(),
                    'recipe_id' => $recipe->id,
                    'product_variant_id' => $productVariant->id,
                    'planned_quantity' => $quantity,
                    'status' => 'planned',
                    'planned_start_date' => $plannedStartDate ?? now(),
                    'planned_end_date' => $this->calculatePlannedEndDate($plannedStartDate, $recipe->production_time),
                    'created_by' => auth()->id(),
                    'notes' => $notes
                ];

                Log::info('Creating production batch with data', [
                    'batch_data' => $batchData
                ]);

                $batch = ProductionBatch::create($batchData);

                Log::info('Production batch created', [
                    'batch_id' => $batch->id
                ]);

                return $batch;
            });
        } catch (\Exception $e) {
            Log::error('Error in createProductionBatch service method', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    public function startProduction(ProductionBatch $batch): void
{
    if ($batch->status !== 'planned') {
        throw new ProductionException("Невозможно начать производство. Неверный статус партии.");
    }

    try {
        DB::transaction(function () use ($batch) {
            // Проверяем наличие материалов
            $availability = $this->checkComponentsAvailability(
                $batch->recipe, 
                $batch->planned_quantity
            );

            if (!$availability['can_produce']) {
                $shortages = collect($availability['components'])
                    ->filter(fn($item) => !$item['is_sufficient'])
                    ->map(fn($item) => "{$item['component']['name']}: нехватка {$item['shortage']}")
                    ->join(', ');
                    
                throw new ProductionException("Недостаточно материалов для производства: $shortages");
            }

            // Резервируем и списываем материалы
            $this->consumeMaterials($batch);

            $batch->update([
                'status' => 'in_progress',
                'started_at' => now()
            ]);

            Log::info('Production batch started', [
                'batch_id' => $batch->id,
                'batch_number' => $batch->batch_number
            ]);
        });
    } catch (\Exception $e) {
        Log::error('Error starting production batch', [
            'batch_id' => $batch->id,
            'error' => $e->getMessage()
        ]);
        throw $e;
    }
}

    public function completeProduction(
        ProductionBatch $batch,
        float $actualQuantity,
        ?string $notes = null
    ): void {
        if ($batch->status !== 'in_progress') {
            throw new \Exception("Невозможно завершить производство. Неверный статус партии.");
        }

        DB::transaction(function () use ($batch, $actualQuantity, $notes) {
            // Рассчитываем себестоимость
            $totalCost = $this->calculateProductionCost($batch);
            $unitCost = $actualQuantity > 0 ? $totalCost / $actualQuantity : 0;

            // Создаем приход готовой продукции
            $this->inventoryService->addStock(
                'product',
                $batch->productVariant->id,
                $actualQuantity,
                $unitCost,
                $batch->recipe->output_unit_id,
                now(),
                auth()->id(),
                "Произведено в партии #{$batch->batch_number}"
            );

            $batch->update([
                'status' => 'completed',
                'actual_quantity' => $actualQuantity,
                'unit_cost' => $unitCost,
                'total_material_cost' => $totalCost,
                'completed_at' => now(),
                'completed_by' => auth()->id(),
                'notes' => $notes ? $batch->notes . "\n" . $notes : $batch->notes
            ]);
        });
    }

    public function cancelProduction(ProductionBatch $batch, string $reason): void
    {
        if (!in_array($batch->status, ['planned', 'in_progress'])) {
            throw new \Exception("Невозможно отменить производство. Неверный статус партии.");
        }

        DB::transaction(function () use ($batch, $reason) {
            // Если производство уже началось, возвращаем материалы
            if ($batch->status === 'in_progress') {
                $this->returnMaterials($batch);
            }

            $batch->update([
                'status' => 'cancelled',
                'notes' => $batch->notes . "\nОтменено: " . $reason
            ]);
        });
    }

    protected function validateProductionPossibility(Recipe $recipe, float $quantity): void
    {
        foreach ($recipe->items as $item) {
            $requiredQuantity = $this->calculateRequiredQuantity($item, $quantity);
            $availableQuantity = $this->inventoryService->getAvailableQuantity(
                $item->component_type,
                $item->component_id
            );

            if ($availableQuantity < $requiredQuantity) {
                throw new \Exception(
                    "Недостаточно материала {$item->component->name}. " .
                    "Требуется: {$requiredQuantity}, Доступно: {$availableQuantity}"
                );
            }
        }
    }

    protected function consumeMaterials(ProductionBatch $batch): void
    {
        $recipe = $batch->recipe;
        $quantity = $batch->planned_quantity;

        foreach ($recipe->items as $item) {
            $requiredQuantity = $this->calculateRequiredQuantity($item, $quantity);

            // Получаем информацию о доступных партиях материала
            $availableBatches = $this->inventoryService->getAvailableBatches(
                $item->component_type,
                $item->component_id,
                $requiredQuantity
            );

            $remainingQuantity = $requiredQuantity;

            foreach ($availableBatches as $inventoryBatch) {
                $quantityToConsume = min($remainingQuantity, $inventoryBatch->quantity);

                // Списываем материал
                $success = $this->inventoryService->removeStock(
                    $item->component_type,
                    $item->component_id,
                    $quantityToConsume,
                    auth()->id(),
                    "Списание для производственной партии #{$batch->batch_number}"
                );

                if (!$success) {
                    throw new ProductionException("Ошибка при списании материала {$item->component->name}");
                }

                // Записываем данные о списании
                $batch->materialConsumptions()->create([
                    'component_type' => $item->component_type,
                    'component_id' => $item->component_id,
                    'inventory_batch_id' => $inventoryBatch->id,
                    'quantity' => $quantityToConsume,
                    'price_per_unit' => $inventoryBatch->price_per_unit,
                    'unit_id' => $item->unit_id
                ]);

                $remainingQuantity -= $quantityToConsume;
                if ($remainingQuantity <= 0) {
                    break;
                }
            }

            if ($remainingQuantity > 0) {
                throw new ProductionException(
                    "Недостаточно материала {$item->component->name} для списания"
                );
            }
        }
    }

    protected function calculateRequiredQuantity($recipeItem, $productionQuantity): float
    {
        $baseQuantity = ($recipeItem->quantity / $recipeItem->recipe->output_quantity) * $productionQuantity;
        $wasteQuantity = $baseQuantity * ($recipeItem->waste_percentage / 100);
        return $baseQuantity + $wasteQuantity;
    }

    protected function calculateProductionCost(ProductionBatch $batch): float
    {
        return $batch->materialConsumptions->sum(function ($consumption) {
            return $consumption->quantity * $consumption->price_per_unit;
        });
    }

    protected function generateBatchNumber(): string
    {
        $prefix = Carbon::now()->format('Ymd');
        $random = strtoupper(Str::random(4));
        return "{$prefix}-{$random}";
    }

    protected function calculatePlannedEndDate(?Carbon $startDate, ?int $productionTime): ?Carbon
    {
        if (!$startDate || !$productionTime) {
            return null;
        }

        return $startDate->addMinutes($productionTime);
    }

    protected function returnMaterials(ProductionBatch $batch): void
    {
        foreach ($batch->materialConsumptions as $consumption) {
            $this->inventoryService->addStock(
                $consumption->component_type,
                $consumption->component_id,
                $consumption->quantity,
                $consumption->price_per_unit,
                $consumption->unit_id,
                now(),
                auth()->id(),
                "Возврат материалов из отменённой партии #{$batch->batch_number}"
            );
        }
    }

    public function checkComponentsAvailability(Recipe $recipe, float $quantity): array
    {
        $availability = [];
        $isAvailable = true;

        foreach ($recipe->items as $item) {
            $required = $this->calculateRequiredQuantity($item, $quantity);
            $available = $item->component->getCurrentStock();

            $availability[$item->id] = [
                'component' => [
                    'id' => $item->component->id,
                    'type' => $item->component_type,
                    'name' => $item->component->name
                ],
                'required' => $required,
                'available' => $available,
                'is_sufficient' => $available >= $required,
                'shortage' => max(0, $required - $available)
            ];

            if ($available < $required) {
                $isAvailable = false;
            }
        }

        return [
            'can_produce' => $isAvailable,
            'components' => $availability
        ];
    }

    public function estimateProductionTime(Recipe $recipe, float $quantity): array
    {
        $baseTime = $recipe->production_time ?? 0;
        $estimatedTime = $baseTime * ($quantity / $recipe->output_quantity);

        $startTime = now();
        $endTime = $startTime->addMinutes($estimatedTime);

        // Проверяем загруженность производства
        $overlappingBatches = ProductionBatch::where('status', 'in_progress')
            ->where(function ($query) use ($startTime, $endTime) {
                $query->whereBetween('planned_start_date', [$startTime, $endTime])
                    ->orWhereBetween('planned_end_date', [$startTime, $endTime]);
            })->get();

        return [
            'estimated_minutes' => $estimatedTime,
            'suggested_start_date' => $startTime,
            'suggested_end_date' => $endTime,
            'has_conflicts' => $overlappingBatches->isNotEmpty(),
            'conflicting_batches' => $overlappingBatches
        ];
    }

    public function getProductionHistory(?array $filters = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = ProductionBatch::with([
            'recipe.productVariant',
            'materialConsumptions.component',
            'createdBy'
        ]);

        if ($filters) {
            if (isset($filters['date_from'])) {
                $query->where('created_at', '>=', $filters['date_from']);
            }
            if (isset($filters['date_to'])) {
                $query->where('created_at', '<=', $filters['date_to']);
            }
            if (isset($filters['status'])) {
                $query->where('status', $filters['status']);
            }
            if (isset($filters['product_variant_id'])) {
                $query->where('product_variant_id', $filters['product_variant_id']);
            }
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    public function getPendingProductions(): Collection
    {
        return ProductionBatch::with([
            'recipe.productVariant',
            'recipe.items.component'
        ])
            ->whereIn('status', ['planned', 'in_progress'])
            ->orderBy('planned_start_date')
            ->get()
            ->map(function ($batch) {
                $availability = $this->checkComponentsAvailability(
                    $batch->recipe,
                    $batch->planned_quantity
                );

                $batch->components_availability = $availability;
                return $batch;
            });
    }

    public function getProductionStatistics(?array $filters = null): array
    {
        $query = ProductionBatch::query();

        if ($filters) {
            if (isset($filters['date_from'])) {
                $query->where('created_at', '>=', $filters['date_from']);
            }
            if (isset($filters['date_to'])) {
                $query->where('created_at', '<=', $filters['date_to']);
            }
        }

        $batches = $query->get();

        return [
            'total_batches' => $batches->count(),
            'completed_batches' => $batches->where('status', 'completed')->count(),
            'failed_batches' => $batches->where('status', 'failed')->count(),
            'average_completion_time' => $batches->where('status', 'completed')
                ->average(function ($batch) {
                    return $batch->completed_at->diffInMinutes($batch->started_at);
                }),
            'total_production_cost' => $batches->sum('total_material_cost'),
            'average_unit_cost' => $batches->where('status', 'completed')
                ->average('unit_cost'),
            'efficiency' => [
                'planned_vs_actual' => $batches->where('status', 'completed')
                    ->average(function ($batch) {
                        return $batch->planned_quantity > 0
                            ? ($batch->actual_quantity / $batch->planned_quantity) * 100
                            : 0;
                    })
            ]
        ];
    }

    protected function getProductVariant(Recipe $recipe): ?\App\Models\ProductVariant
    {
        Log::info('Getting product variant for recipe', [
            'recipe_id' => $recipe->id
        ]);

        // Загружаем связь из таблицы product_recipes
        $productRecipe = DB::table('product_recipes')
            ->where('recipe_id', $recipe->id)
            ->first();

        Log::info('Product recipe relation found', [
            'product_recipe' => $productRecipe
        ]);

        if (!$productRecipe) {
            Log::warning('No product recipe relation found', [
                'recipe_id' => $recipe->id
            ]);
            return null;
        }

        // Если указан конкретный вариант
        if ($productRecipe->product_variant_id) {
            $variant = ProductVariant::find($productRecipe->product_variant_id);
            Log::info('Found specific variant', [
                'variant' => $variant ? $variant->toArray() : null
            ]);
            return $variant;
        }


        Log::warning('No suitable variant found for recipe', [
            'recipe_id' => $recipe->id,
            'product_id' => $productRecipe->product_id
        ]);

        return null;
    }
}
