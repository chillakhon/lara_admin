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
        $isAdmin = $request->boolean('admin', false);

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
            'avg_rating' => $this->reviews_avg_rating ? round($this->reviews_avg_rating, 2) : null,
            // 'allow_preorder' => $this->allow_preorder,
            // 'after_purchase_processing_time' => $this->after_purchase_processing_time,
            // 'created_at' => $this->created_at,
            // 'updated_at' => $this->updated_at,
            // 'deleted_at' => $this->deleted_at,
            'price' => $this->price,
            'old_price' => $this->old_price,
            $this->mergeWhen($isAdmin, [
                'stock_quantity' => $this->inventory_balance,
            ]),
            'cost_price' => $this->cost_price,
            // 'currency' => $this->currency,
            // 'stock_quantity' => $this->stock_quantity,
            // 'min_order_quantity' => $this->min_order_quantity,
            // 'max_order_quantity' => $this->max_order_quantity,
            // 'is_featured' => $this->is_featured,
            'is_new' => $this->is_new,
            'discount_price' => $this->discount_price,
            'discount_percentage' => $this->discount_percentage,
            'total_discount' => $this->total_discount,
            'discount_id' => $this->discount_id,
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'weight' => $this->weight,
            'length' => $this->length,
            'width' => $this->width,
            'height' => $this->height,
            // 'image_path' => $this->image_path,
            'main_image' => $this->main_image ? new ImageResource($this->main_image) : null,
            'images' => ImageResource::collection($this->images ?? []),
            'colors' => ColorResource::collection($this->colors ?? []),
            'variants' => ProductVariantNumberTwoResource::collection($this->variants ?? []),
            'default_unit' => $this->defaultUnit ? new UnitResource($this->defaultUnit) : null,
            // 'discountable' => new DiscountableResource($this->whenLoaded('discountable')),
        ];
    }
}
