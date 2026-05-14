<?php

namespace App\DTOs;

class PaymentDTO
{
    public function __construct(
        public readonly int $orderId,
        public readonly float $amount,
        public readonly string $currency,
        public readonly array $items,
        public readonly array $customer,
        public readonly ?string $description = null,
        public readonly ?array $additionalData = null
    ) {}

    public static function fromOrder(\App\Models\Order $order): self
    {
        return new self(
            orderId: $order->id,
            amount: $order->total_amount,
            currency: 'RUB',
            items: $order->items->map(fn($item) => [
                'name' => $item->product->name,
                'quantity' => $item->quantity,
                'price' => $item->price,
                'sum' => $item->quantity * $item->price,
            ])->toArray(),
            customer: [
                'email' => $order->client->email,
                'phone' => $order->client->phone,
                'name' => $order->client->full_name,
            ],
            description: "Оплата заказа #{$order->order_number}"
        );
    }
} 