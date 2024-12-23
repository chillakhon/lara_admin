<?php

namespace App\DTOs;

class ReceiptDTO
{
    public function __construct(
        public readonly int $paymentId,
        public readonly array $items,
        public readonly array $customer,
        public readonly float $total,
        public readonly string $paymentMethod,
        public readonly ?string $receiptNumber = null,
        public readonly ?array $additionalData = null
    ) {}

    public static function fromPayment(\App\Models\Payment $payment): self
    {
        return new self(
            paymentId: $payment->id,
            items: $payment->order->items->map(fn($item) => [
                'name' => $item->product->name,
                'quantity' => $item->quantity,
                'price' => $item->price,
                'sum' => $item->quantity * $item->price,
                'vat' => '20%', // Настройте под ваши нужды
            ])->toArray(),
            customer: [
                'email' => $payment->order->client->email,
                'phone' => $payment->order->client->phone,
            ],
            total: $payment->amount,
            paymentMethod: $payment->provider
        );
    }
} 