<?php

namespace App\Http\Resources\Segment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SegmentClientResource extends JsonResource
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
            'email' => $this->email,
            'profile' => [
                'first_name' => $this->profile?->first_name,
                'last_name' => $this->profile?->last_name,
                'full_name' => $this->profile?->full_name,
                'phone' => $this->profile?->phone,
                'birthday' => $this->profile?->birthday,
                'address' => $this->profile?->address,
            ],

            'full_name' => $this->profile?->first_name . ' ' . $this->profile?->last_name,
            // Статистика по заказам
            'orders_count' => $this->orders_count ?? 0,
            'total_amount' => round($this->total_amount ?? 0, 2),
            'average_check' => round($this->average_check ?? 0, 2),

            // Дата добавления в сегмент
            'added_to_segment_at' => $this->pivot?->added_at
                ? \Carbon\Carbon::parse($this->pivot->added_at)?->format('d.m.Y H:i:s')
                : null,

            // Дата регистрации
            'registered_at' => $this->created_at?->format('d.m.Y'),

            // Теги (сегменты клиента)
            'segments' => $this->when(
                $this->relationLoaded('segments'),
                function () {
                    return $this->segments->pluck('name')->toArray();
                }
            ),

            // Промокоды клиента
            'promo_codes' => $this->when(
                $this->relationLoaded('promoCodes'),
                function () {
                    return $this->promoCodes->pluck('code')->toArray();
                }
            ),
        ];
    }
}
