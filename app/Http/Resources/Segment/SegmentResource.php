<?php

namespace App\Http\Resources\Segment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SegmentResource extends JsonResource
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
            'name' => $this->name,
            'description' => $this->description,
            'conditions' => $this->conditions,
            'is_active' => $this->is_active,
            'recalculate_frequency' => $this->recalculate_frequency,
            'last_recalculated_at' => $this->last_recalculated_at?->format('d.m.Y H:i:s'),
            'clients_count' => $this->whenCounted('clients'),
            'promo_codes_count' => $this->whenCounted('promoCodes'),
            'created_at' => $this->created_at->format('d.m.Y H:i:s'),
            'updated_at' => $this->updated_at->format('d.m.Y H:i:s'),

            // Связи (загружаются только если есть)
            'clients' => SegmentClientResource::collection($this->whenLoaded('clients')),
            'promo_codes' => PromoCodeBriefResource::collection($this->whenLoaded('promoCodes')),
        ];
    }
}
