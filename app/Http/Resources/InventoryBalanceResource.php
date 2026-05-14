<?php

namespace App\Http\Resources;

use App\Models\Material;
use App\Models\Product;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryBalanceResource extends JsonResource
{
    public function toArray($request)
    {
        $item = $this->item;
        
        if (!$item) {
            return null;
        }

        return [
            'id' => $this->id,
            'item_type' => $this->item_type,
            'item_id' => $this->item_id,
            'quantity' => $this->total_quantity,
            'average_price' => $this->average_price,
            'unit' => $this->unit?->name,
            'item' => [
                'id' => $item->id,
                'name' => $this->item_type === 'material' ? $item->title : $item->name,
                'has_variants' => $this->item_type === 'product' ? $item->has_variants : false,
                'variants' => $this->when(
                    $this->item_type === 'product' && $item->has_variants,
                    function() use ($item) {
                        return $item->variants->map(function($variant) {
                            return [
                                'id' => $variant->id,
                                'name' => $variant->name,
                                'sku' => $variant->sku,
                                'inventory_balance' => [
                                    'quantity' => $variant->inventoryBalance?->total_quantity ?? 0,
                                ],
                                'unit' => [
                                    'id' => $variant->unit?->id,
                                    'name' => $variant->unit?->name,
                                ],
                            ];
                        });
                    }
                ),
            ],
        ];
    }
}
