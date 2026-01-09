<?php

namespace App\Http\Resources\GiftCard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GiftCardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'nominal' => (float) $this->nominal,
            'balance' => (float) $this->balance,
            'type' => $this->type,
            'status' => $this->status,

            // Отправитель
            'sender' => [
                'name' => $this->sender_name,
                'email' => $this->sender_email,
                'phone' => $this->sender_phone,
            ],

            // Получатель
            'recipient' => [
                'name' => $this->recipient_name,
                'email' => $this->recipient_email,
                'phone' => $this->recipient_phone,
            ],

            // Доставка
            'delivery' => [
                'channel' => $this->delivery_channel,
                'message' => $this->message,
                'scheduled_at' => $this->scheduled_at?->toIso8601String(),
                'timezone' => $this->timezone,
                'sent_at' => $this->sent_at?->toIso8601String(),
                'delivered_at' => $this->delivered_at?->toIso8601String(),
            ],

            // Статистика
            'usage' => [
                'is_active' => $this->isActive(),
                'is_fully_used' => $this->isFullyUsed(),
                'used_amount' => (float) ($this->nominal - $this->balance),
                'usage_percentage' => $this->nominal > 0
                    ? round((($this->nominal - $this->balance) / $this->nominal) * 100, 2)
                    : 0,
            ],

            // Связи (загружаются только если запрошены)
            'purchase_order' => $this->whenLoaded('purchaseOrder', function () {
                return [
                    'id' => $this->purchaseOrder->id,
                    'order_number' => $this->purchaseOrder->order_number,
                    'created_at' => $this->purchaseOrder->created_at->toIso8601String(),
                ];
            }),

            'transactions' => GiftCardTransactionResource::collection($this->whenLoaded('transactions')),

            'used_in_orders' => $this->whenLoaded('usedInOrders', function () {
                return $this->usedInOrders->map(fn($order) => [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'amount_used' => (float) $order->gift_card_amount,
                    'created_at' => $order->created_at->toIso8601String(),
                ]);
            }),

            // Timestamps
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
