<?php

namespace App\Http\Resources\Segment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SegmentStatisticsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'clients_count' => $this->clientsCount,
            'total_amount' => [
                'value' => round($this->totalAmount, 2),
                'formatted' => number_format($this->totalAmount, 2, '.', ' ') . ' ₽'
            ],
            'average_check' => [
                'value' => round($this->averageCheck, 2),
                'formatted' => number_format($this->averageCheck, 2, '.', ' ') . ' ₽'
            ],
            'total_orders' => $this->totalOrders,

            // Топ клиентов по сумме покупок
            'top_clients' => $this->when(
                !empty($this->clientsBreakdown),
                function () {
                    return collect($this->clientsBreakdown)->map(function ($client) {
                        return [
                            'id' => $client->id,
                            'full_name' => $client->full_name,
                            'email' => $client->email,
                            'orders_count' => $client->orders_count,
                            'total_amount' => round($client->total_amount, 2),
                            'average_check' => round($client->average_check, 2),
                        ];
                    })->toArray();
                }
            ),
        ];
    }
}
