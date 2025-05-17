<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductNumberTwoResouce extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            // Your custom structure based on the JSON you shared
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'type' => $this->type,
            'default_unit_id' => $this->default_unit_id,
            'is_active' => $this->is_active,
            'has_variants' => $this->has_variants,
            // 'allow_preorder' => $this->allow_preorder,
            // 'after_purchase_processing_time' => $this->after_purchase_processing_time,
            // 'created_at' => $this->created_at,
            // 'updated_at' => $this->updated_at,
            // 'deleted_at' => $this->deleted_at,
            'price' => $this->price,
            'old_price' => $this->old_price,
            'stock_quantity' => $this->inventory_balance,
            // 'cost_price' => $this->cost_price,
            // 'currency' => $this->currency,
            // 'stock_quantity' => $this->stock_quantity,
            // 'min_order_quantity' => $this->min_order_quantity,
            // 'max_order_quantity' => $this->max_order_quantity,
            // 'is_featured' => $this->is_featured,
            'is_new' => $this->is_new,
            'discount_price' => $this->discount_price,
            'discount_percentage' => $this->discount_percentage,
            'total_discount' => $this->total_discount,
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'weight' => $this->weight,
            'length' => $this->length,
            'width' => $this->width,
            'height' => $this->height,
            // 'image_path' => $this->image_path,
            'images' => ImageResource::collection($this->whenLoaded('images')),
            'colors' => ColorResource::collection($this->whenLoaded('colors')),
            'variants' => ProductVariantNumberTwoResource::collection($this->whenLoaded('variants')),
            'default_unit' => new UnitResource($this->whenLoaded('defaultUnit')),
            // 'discountable' => new DiscountableResource($this->whenLoaded('discountable')),
        ];
    }
}
