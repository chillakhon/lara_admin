<?php

namespace App\Services;

use App\Models\Recipe;
use App\Models\InventoryBatch;

class RecipeService
{
    public const COST_STRATEGY_AVERAGE = 'average';
    public const COST_STRATEGY_FIFO = 'fifo';
    public const COST_STRATEGY_LIFO = 'lifo';
    public const COST_STRATEGY_NEWEST = 'newest_batch';

    public function calculateEstimatedCost(Recipe $recipe, string $costStrategy, float $quantity): array
    {
        $total = 0;
        $components = [];

        foreach ($recipe->material_items as $item) {
            $requiredQuantity = $this->calculateRequiredQuantity($item, $quantity);
            $pricePerUnit = $this->getComponentPrice($item->component, $requiredQuantity, $costStrategy);
            $itemCost = $pricePerUnit * $requiredQuantity;

            $components[] = [
                'name' => $item->component->name ?? $item->component->title,
                'quantity' => $requiredQuantity,
                'unit' => $item->unit->abbreviation,
                'price_per_unit' => $pricePerUnit,
                'total_cost' => $itemCost,
            ];

            $total += $itemCost;
        }

        return [
            'materials_cost' => $total,
            'components' => $components
        ];
    }

    protected function calculateRequiredQuantity($item, float $productionQuantity): float
    {
        $baseQuantity = ($item->quantity / $item->recipe->output_quantity) * $productionQuantity;
        $wasteQuantity = $baseQuantity * ($item->waste_percentage / 100);
        return $baseQuantity + $wasteQuantity;
    }

    protected function getComponentPrice($component, float $requiredQuantity, string $strategy): float
    {
        return match($strategy) {
            self::COST_STRATEGY_AVERAGE => $this->getAveragePrice($component),
            self::COST_STRATEGY_FIFO => $this->getFifoPrice($component, $requiredQuantity),
            self::COST_STRATEGY_LIFO => $this->getLifoPrice($component, $requiredQuantity),
            default => throw new \InvalidArgumentException("Неизвестная стратегия расчета стоимости: {$strategy}")
        };
    }

    protected function getAveragePrice($component): float
    {
        return $component->inventoryBalance?->average_price ?? 0;
    }

    protected function getFifoPrice($component, float $requiredQuantity): float
    {
        $batches = $component->inventoryBatches()
            ->where('quantity', '>', 0)
            ->orderBy('received_date', 'asc')
            ->get();

        return $this->calculateWeightedPrice($batches, $requiredQuantity);
    }

    protected function getLifoPrice($component, float $requiredQuantity): float
    {
        $batches = $component->inventoryBatches()
            ->where('quantity', '>', 0)
            ->orderBy('received_date', 'desc')
            ->get();

        return $this->calculateWeightedPrice($batches, $requiredQuantity);
    }

    protected function getNewestBatchPrice($component): float
    {
        $newestBatch = $component->inventoryBatches()
            ->orderBy('received_date', 'desc')
            ->first();

        return $newestBatch?->price_per_unit ?? 0;
    }

    protected function calculateWeightedPrice($batches, float $requiredQuantity): float
    {
        $remainingQuantity = $requiredQuantity;
        $totalCost = 0;
        $totalQuantityFound = 0;

        foreach ($batches as $batch) {
            $quantityFromBatch = min($remainingQuantity, $batch->quantity);
            $totalCost += $quantityFromBatch * $batch->price_per_unit;
            $totalQuantityFound += $quantityFromBatch;
            $remainingQuantity -= $quantityFromBatch;

            if ($remainingQuantity <= 0) {
                break;
            }
        }

        // Если нашли хоть какое-то количество, рассчитываем среднюю цену
        if ($totalQuantityFound > 0) {
            return $totalCost / $totalQuantityFound;
        }

        return 0;
    }

    public function getAvailableCostStrategies(): array
    {
        return [
            self::COST_STRATEGY_AVERAGE => 'По средней цене',
            self::COST_STRATEGY_FIFO => 'FIFO (первым пришел, первым ушел)',
            self::COST_STRATEGY_LIFO => 'LIFO (последним пришел, первым ушел)',
            self::COST_STRATEGY_NEWEST => 'По цене новейшей партии'
        ];
    }
}
