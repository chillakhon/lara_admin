<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductNumberThreeResource extends JsonResource
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
            'default_unit_id' => $this->default_unit_id,
            'price' => $this->price,
            'old_price' => $this->old_price,
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
            'default_unit' => $this->defaultUnit ? new UnitResource($this->defaultUnit) : null,
        ];
    }
}