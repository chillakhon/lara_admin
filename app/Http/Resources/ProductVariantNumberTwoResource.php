<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductVariantNumberTwoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isAdmin = $request->boolean('admin', false);

        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'name' => $this->name,
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'price' => $this->price,
            'old_price' => $this->old_price,
            // 'cost_price' => $this->cost_price,
            $this->mergeWhen($isAdmin, [
                'stock_quantity' => $this->inventory_balance,
            ]),
            // 'stock_quantity' => $this->inventory_balance,
            // 'additional_cost' => $this->additional_cost,
            'type' => $this->type,
            'unit_id' => $this->unit_id,
            'is_active' => $this->is_active,
            // 'created_at' => $this->created_at,
            // 'updated_at' => $this->updated_at,
            // 'deleted_at' => $this->deleted_at,
            // 'inventory_balance' => $this->inventory_balance,
            'discount_percentage' => $this->discount_percentage,
            'total_discount' => $this->total_discount,
            'unit' => $this->unit ? new UnitResource($this->unit) : null,
            'colors' => ColorResource::collection($this->colors ?? []),
            'images' => ImageResource::collection($this->images ?? []),
            // 'discountable' => new DiscountableResource($this->whenLoaded('discountable')),
        ];
    }
}
