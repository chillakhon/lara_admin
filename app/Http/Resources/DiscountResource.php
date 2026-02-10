<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DiscountResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'value' => $this->value,
            'is_active' => $this->is_active,
            'starts_at' => $this->starts_at,
            'ends_at' => $this->ends_at,
            'starts_at_formatted' => $this->starts_at ? $this->starts_at->format('d.m.Y') : 'Сразу',
            'ends_at_formatted' => $this->ends_at ? $this->ends_at->format('d.m.Y') : 'Бессрочно',
            'is_unlimited' => $this->ends_at === null,
            'priority' => $this->priority,
            'conditions' => $this->conditions,
            'discount_type' => $this->discount_type,
            'products' => ProductResource::collection($this->whenLoaded('products')),
            'categories' => CategoryResource::collection($this->whenLoaded('categories')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}
