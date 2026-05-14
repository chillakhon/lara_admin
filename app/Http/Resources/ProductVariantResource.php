<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductVariantResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'article' => $this->article,
            'price' => $this->price,
            'stock_quantity' => $this->stock_quantity,
            'size' => $this->whenLoaded('size', function () {
                return [
                    'id' => $this->product->size->id,
                    'name' => $this->product->size->name,
                ];
            }),
            'color' => $this->whenLoaded('colorOptionValue', function () {
                return [
                    'id' => $this->colorOptionValue->color->id,
                    'title' => $this->colorOptionValue->color->title,
                    'code' => $this->colorOptionValue->color->code,
                ];
            }),
            'images' => ImageResource::collection($this->whenLoaded('images')),
        ];
    }
}
