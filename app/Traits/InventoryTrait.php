<?php

namespace App\Traits;

use App\Models\InventoryBalance;

trait InventoryTrait
{

    public function validateProductionPossibility($items): array
    {
        $materials = [];
        foreach ($items as $item) {
            $find_product = InventoryBalance
                ::where('item_type', $this->get_model_by_type($item['component_type']))
                ->where('item_id', $item['component_id'])
                ->first();

            if (!$find_product) {
                $materials[] = $item;
                continue;
            }

            if ($find_product->total_quantity < $item['quantity']) {
                $item["not_enough_qty"] = $item['quantity'] - $find_product->total_quantity;
                $materials[] = $item;
                continue;
            }
        }

        return $materials;
    }


    public function validateRemoveProductionPossibility($items)
    {
        $output_products = [];
        foreach ($items as $item) {
            $find_product = InventoryBalance
                ::where('item_type', $item['output_type'])
                ->where('item_id', $item['output_id'])
                ->first();

            if (!$find_product) {
                $output_products[] = $item;
                continue;
            }

            if ($find_product->total_quantity < $item['qty']) {
                $item["not_enough_qty"] = $item['qty'] - $find_product->total_quantity;
                $output_products[] = $item;
                continue;
            }
        }
        return $output_products;
    }


    public function remove_component_from_inventory($component)
    {

        $inventory_item = InventoryBalance
            ::where('item_type', $this->get_model_by_type($component['component_type']))
            ->where('item_id', $component['component_id'])
            ->first();

        if (!$inventory_item) {
            $inventory_item = InventoryBalance::create([
                'item_type' => $this->get_model_by_type($component['component_type']),
                'item_id' => $component['component_id'],
                'total_quantity' => 0,
            ]);
        }

        if ($inventory_item->total_quantity > 0) {
            $inventory_item->decrement('total_quantity', $component['quantity']);
        }
    }

    public function add_component_to_inventory($component)
    {

        $inventory_item = InventoryBalance
            ::where('item_type', $this->get_model_by_type($component['component_type']))
            ->where('item_id', $component['component_id'])
            ->first();

        if (!$inventory_item) {
            $inventory_item = InventoryBalance::create([
                'item_type' => $this->get_model_by_type($component['component_type']),
                'item_id' => $component['component_id'],
                'total_quantity' => 0,
            ]);
        }

        $inventory_item->increment('total_quantity', $component['quantity']);
    }

    public function checkDiff(
        $material_details,
        $prevoius_material_details,
    ): array
    {
        $plus_inventory = [];

        $previous_index = [];
        $diff_result = [];

        foreach ($prevoius_material_details as $item) {
            $key = $item['component_type'] . '_' . $item['component_id'];
            $previous_index[$key] = $item;
        }

        foreach ($material_details as $item) {
            $key = $item['component_type'] . '_' . $item['component_id'];

            $old_qty = isset($previous_index[$key]) ? $previous_index[$key]['quantity'] : 0;
            $new_qty = $item['quantity'];


            $qty_diff = 0.0;

            // because output product will not produced yet
            // check for materials only
            if ($new_qty < $old_qty) {
                $plus_inventory[] = $this->prepare_item($item, $old_qty - $new_qty);
                unset($previous_index[$key]);
                continue;
            }

            $qty_diff = $new_qty - $old_qty;

            if ($qty_diff != 0) {
                $diff_result[] = $this->prepare_item($item, $qty_diff);
            }

            unset($previous_index[$key]);
        }

        foreach ($previous_index as $item) {
            $diff_result[] = $this->prepare_item($item, $item['quantity']);
        }

        if (count($diff_result) >= 1) {
            return [
                "success" => $this->checkQtyDifference($diff_result),
                "for_minus_inventory" => $diff_result,
                "for_plus_inventory" => $plus_inventory
            ];
        }

        return [
            "success" => true,
            "for_minus_inventory" => $diff_result,
            "for_plus_inventory" => $plus_inventory
        ];
    }

    private function prepare_item($item, $qty)
    {
        return [
            'component_type' => $item['component_type'],
            'component_id' => $item['component_id'],
            'quantity' => $qty,
        ];
    }

    private function checkQtyDifference(array $diff_results)
    {
        $inventory_balances = InventoryBalance::get()
            ->keyBy(function ($item) {
                return $this->get_type_by_model($item->item_type) . '_' . $item->item_id;
            });

        foreach ($diff_results as $diffItem) {
            $key = $diffItem['component_type'] . '_' . $diffItem['component_id'];

            if (!isset($inventory_balances[$key])) {
                return false;
            }

            $inventory_qty = $inventory_balances[$key]->total_quantity;

            if ($diffItem['quantity'] > $inventory_qty) {
                return false; // qty in diff is more than we have in stock
            }
        }

        return true;
    }
}
