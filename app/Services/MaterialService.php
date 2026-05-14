<?php

namespace App\Services;

use App\Models\Material;
use App\Models\Product;
use App\Models\InventoryBalance;

class MaterialService
{
    protected $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    public function createMaterial(array $data)
    {
        $material = Material::create([
            'title' => $data['title'],
            'unit_id' => $data['unit_id'],
        ]);

        // Создаем начальный баланс с нулевым количеством
        InventoryBalance::create([
            'item_type' => 'material',
            'item_id' => $material->id,
            'total_quantity' => 0,
            'average_price' => 0,
            'unit_id' => $data['unit_id'],
        ]);

        return $material;
    }

    public function updateMaterial(Material $material, array $data)
    {
        $material->update([
            'title' => $data['title'],
            'unit_id' => $data['unit_id'],
        ]);

        // Если единица измерения изменилась, обновляем её в балансе
        if ($material->inventoryBalance->unit_id !== $data['unit_id']) {
            $material->inventoryBalance->update([
                'unit_id' => $data['unit_id'],
            ]);
        }

        return $material;
    }

    public function deleteMaterial(Material $material)
    {
        // Удаляем связанный баланс
        $material->inventoryBalance()->delete();

        // Удаляем материал
        $material->delete();
    }

    public function calculateProductCost(Product $product)
    {
        $totalCost = 0;

        foreach ($product->components as $component) {
            $material = $component->material;
            $materialCost = $material->inventoryBalance->average_price * $component->quantity;
            $totalCost += $materialCost;
        }

        return $totalCost;
    }

    public function getMaterialStock(Material $material)
    {
        return $material->inventoryBalance->total_quantity ?? 0;
    }

    public function getMaterialAveragePrice(Material $material)
    {
        return $material->inventoryBalance->average_price ?? 0;
    }

    public function checkStockAvailability(Material $material, float $requiredQuantity)
    {
        $availableStock = $this->getMaterialStock($material);
        return $availableStock >= $requiredQuantity;
    }
}
