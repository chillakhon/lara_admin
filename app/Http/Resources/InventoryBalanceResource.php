<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryBalanceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'item_id' => $this->item_id,
            'item_type' => $this->item_type,
            'name' => $this->item->title ?? $this->item->name ?? 'Unknown',
            'sku' => $this->item->sku ?? 'N/A',
            'type' => $this->item_type === 'App\\Models\\Material' ? 'Материал' : 'Продукт',
            'quantity' => $this->total_quantity,
            'unit' => $this->unit->abbreviation,
            'average_price' => $this->average_price,
        ];
    }
}
