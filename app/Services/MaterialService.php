<?php

namespace App\Services;

use App\Models\Material;
use App\Models\Product;

class MaterialService
{
    public function createMaterial(array $data)
    {
        $material = Material::create([
            'title' => $data['title'],
            'unit_of_measurement' => $data['unit_of_measurement'],
            'cost_per_unit' => $data['cost_per_unit'],
        ]);

        if (isset($data['conversion'])) {
            $material->conversions()->create($data['conversion']);
        }

        return $material;
    }

    public function calculateProductCost(Product $product)
    {
        $totalCost = 0;

        foreach ($product->components as $component) {
            $materialCost = $component->material->cost_per_unit * $component->quantity;
            $totalCost += $materialCost;
        }

        return $totalCost;
    }
}
