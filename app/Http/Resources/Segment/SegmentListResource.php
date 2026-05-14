<?php

namespace App\Http\Resources\Segment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SegmentListResource extends JsonResource
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
            'recalculate_frequency' => $this->recalculate_frequency,
            'is_active' => $this->is_active,
            'clients_count' => $this->clients_count ?? 0,
            'promo_codes_count' => $this->promo_codes_count ?? 0,
            'created_at' => $this->created_at?->format('d.m.Y'),
            'last_recalculated_at' => $this->last_recalculated_at?->format('d.m.Y H:i:s'),

            'conditions' => $this->conditions ?? [],

            // Краткая статистика (если передана)
            'statistics' => $this->when(isset($this->statistics), function () {
                return [
                    'total_amount' => round($this->statistics['total_amount'] ?? 0, 2),
                    'average_check' => round($this->statistics['average_check'] ?? 0, 2),
                    'total_orders' => $this->statistics['total_orders'] ?? 0,
                ];
            }),

            // Промокоды (только коды для отображения в таблице)
            'promo_codes' => $this->when(
                $this->relationLoaded('promoCodes'),
                function () {
                    return $this->promoCodes->pluck('code')->take(3)->toArray();
                }
            ),
        ];
    }
}
