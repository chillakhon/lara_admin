<?php

namespace App\Http\Resources\Segment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PromoCodeBriefResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'description' => $this->description,
            'discount_amount' => $this->discount_amount,
            'discount_type' => $this->discount_type,
            'discount_type_label' => $this->discount_type === 'percentage' ? '%' : '₽',
            'expires_at' => $this->expires_at?->format('d.m.Y'),
            'is_active' => $this->is_active,
            'auto_apply' => $this->pivot?->auto_apply ?? true,

            // Изображение
            'image_url' => $this->image
                ? asset('storage/' . $this->image)
                : null,
        ];
    }
}
