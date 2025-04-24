<?php


namespace App\Services;

use App\Models\Recipe;
use App\Models\ProductionBatch;
use App\Models\CostCategory;

class ProductionCostService
{
    public function calculateEstimatedCosts(Recipe $recipe, float $quantity): array
    {
        $costs = [
            'materials' => 0,
            'labor' => 0,
            'overhead' => 0,
            'management' => 0,
            'total' => 0,
            'per_unit' => 0,
            'details' => []
        ];

        // Расчет затрат на материалы
        foreach ($recipe->material_items as $item) {
            $itemCost = $this->calculateMaterialCost($item, $quantity);
            $costs['materials'] += $itemCost['total']; // Используем значение total из массива
            $costs['details'][] = [
                'type' => 'material',
                'name' => $item->component->name ?? $item->component->title,
                'amount' => $itemCost['total'],
                'quantity' => $itemCost['quantity'],
                'price_per_unit' => $itemCost['price']
            ];
        }

        // Расчет других затрат на основе ставок
        if ($recipe->costRates) {
            foreach ($recipe->costRates as $rate) {
                $categoryType = $rate->category->type;
                $amount = $this->calculateRateCost($rate, $quantity);

                $costs[$categoryType] += $amount;
                $costs['details'][] = [
                    'type' => $categoryType,
                    'name' => $rate->category->name,
                    'amount' => $amount,
                    'is_fixed' => $rate->fixed_rate > 0,
                    'rate_per_unit' => $rate->rate_per_unit,
                    'fixed_rate' => $rate->fixed_rate
                ];
            }
        }

        // Расчет итогов
        $costs['total'] = $costs['materials'] + $costs['labor'] +
            $costs['overhead'] + $costs['management'];
        $costs['per_unit'] = $quantity > 0 ? $costs['total'] / $quantity : 0;

        return $costs;
    }


    protected function calculateMaterialCost($recipeItem, float $productionQuantity): array
    {
        // Расчет требуемого количества с учетом процента отходов
        $baseQuantity = ($recipeItem->quantity / $recipeItem->recipe->output_quantity) * $productionQuantity;
        $wasteQuantity = $baseQuantity * ($recipeItem->waste_percentage ?? 0 / 100);
        $totalQuantity = $baseQuantity + $wasteQuantity;

        // Получаем цену компонента
        $price = $recipeItem->component->inventoryBalance?->average_price ?? 0;

        return [
            'quantity' => $totalQuantity,
            'price' => $price,
            'total' => $totalQuantity * $price
        ];
    }

    protected function calculateRateCost($rate, float $quantity): float
    {
        return ($rate->rate_per_unit * $quantity) + ($rate->fixed_rate ?? 0);
    }

    public function recordActualCosts(ProductionBatch $batch, array $costs): void
    {
        foreach ($costs as $categoryId => $amount) {
            $batch->costs()->create([
                'cost_category_id' => $categoryId,
                'amount' => $amount
            ]);
        }

        // Обновляем общую стоимость партии
        $totalCost = $batch->costs->sum('amount');
        $batch->update([
            'total_cost' => $totalCost,
            'unit_cost' => $batch->actual_quantity > 0 ?
                $totalCost / $batch->actual_quantity : 0
        ]);
    }

    public function getDefaultCostCategories(): array
    {
        return [
            [
                'name' => 'Оплата труда операторов',
                'type' => CostCategory::TYPE_LABOR,
                'description' => 'Заработная плата производственного персонала'
            ],
            [
                'name' => 'Амортизация оборудования',
                'type' => CostCategory::TYPE_OVERHEAD,
                'description' => 'Расходы на амортизацию производственного оборудования'
            ],
            [
                'name' => 'Электроэнергия',
                'type' => CostCategory::TYPE_OVERHEAD,
                'description' => 'Затраты на электроэнергию в процессе производства'
            ],
            [
                'name' => 'Управление производством',
                'type' => CostCategory::TYPE_MANAGEMENT,
                'description' => 'Затраты на управление производственным процессом'
            ]
        ];
    }

    public function analyzeCosts(ProductionBatch $batch): array
    {
        $plannedCosts = $this->calculateEstimatedCosts(
            $batch->recipe,
            $batch->planned_quantity
        );

        $actualCosts = [
            'materials' => 0,
            'labor' => 0,
            'overhead' => 0,
            'management' => 0,
            'total' => 0
        ];

        foreach ($batch->costs as $cost) {
            $type = $cost->category->type;
            $actualCosts[$type] += $cost->amount;
            $actualCosts['total'] += $cost->amount;
        }

        return [
            'planned' => $plannedCosts,
            'actual' => $actualCosts,
            'variance' => [
                'materials' => $actualCosts['materials'] - $plannedCosts['materials'],
                'labor' => $actualCosts['labor'] - $plannedCosts['labor'],
                'overhead' => $actualCosts['overhead'] - $plannedCosts['overhead'],
                'management' => $actualCosts['management'] - $plannedCosts['management'],
                'total' => $actualCosts['total'] - $plannedCosts['total']
            ],
            'variance_percentage' => $plannedCosts['total'] > 0 ?
                (($actualCosts['total'] - $plannedCosts['total']) / $plannedCosts['total']) * 100 : 0
        ];
    }
}
