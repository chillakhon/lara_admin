<?php

namespace App\Http\Resources\GiftCard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GiftCardTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'amount' => (float) $this->amount,
            'balance_before' => (float) $this->balance_before,
            'balance_after' => (float) $this->balance_after,
            'notes' => $this->notes,

            // Связанный заказ (если есть)
            'order' => $this->when($this->order_id, function () {
                return $this->whenLoaded('order', function () {
                    return [
                        'id' => $this->order->id,
                        'order_number' => $this->order->order_number,
                        'created_at' => $this->order->created_at->toIso8601String(),
                    ];
                });
            }),

            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
