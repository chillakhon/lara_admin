<?php

namespace App\Http\Controllers\Api\Public\Order;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;

class PublicOrderController extends Controller
{
    /**
     * Публичный просмотр заказа по 32-символьному view_token.
     * Используется на витрине: /orders/{view_token}.
     *
     * Возвращаем безопасный плоский payload — без vendor-полей вроде
     * client_id, payment_id, IP-адресов и т.п.
     */
    public function show(string $viewToken): JsonResponse
    {
        // Валидация формата токена — 32 hex-символа.
        if (! preg_match('/^[a-f0-9]{32}$/i', $viewToken)) {
            return response()->json([
                'success' => false,
                'message' => 'Заказ не найден',
            ], 404);
        }

        /** @var Order|null $order */
        $order = Order::query()
            ->with([
                'items.product.images',
                'items.variant.images',
                'items.color',
                'address',
                'deliveryMethod',
                'deliveryTarget',
                'client.profile',
                'promoCode',
            ])
            ->where('view_token', $viewToken)
            ->first();

        if (! $order) {
            return response()->json([
                'success' => false,
                'message' => 'Заказ не найден',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'order' => $this->formatOrder($order),
        ]);
    }

    private function formatOrder(Order $order): array
    {
        $address = $order->address;
        $profile = $order->client?->profile;

        $recipientName = trim(implode(' ', array_filter([
            $address?->recipient_last_name,
            $address?->recipient_first_name,
            $address?->recipient_middle_name,
        ]))) ?: trim(implode(' ', array_filter([
            $order->last_name ?? $profile?->last_name,
            $order->first_name ?? $profile?->first_name,
        ]))) ?: null;

        $recipientPhone = $address?->recipient_phone
            ?? $order->phone
            ?? $profile?->phone
            ?? null;

        $deliveryAddressText = null;
        if ($address) {
            $deliveryAddressText = trim(implode(', ', array_filter([
                $address->country,
                $address->region,
                $address->city,
                $address->address,
            ])));
            if ($deliveryAddressText === '') {
                $deliveryAddressText = null;
            }
        }

        $deliveryMethodLabel = $order->deliveryMethod?->name
            ?? $order->legacy_delivery_method
            ?? null;

        $deliveryTargetLabel = $order->deliveryTarget?->name ?? null;

        $items = $order->items->map(function ($item) {
            $name = $item->product?->name ?? $item->legacy_name ?? '—';
            $variantName = $item->variant?->name;
            $colorName = $item->color?->name;
            $extras = array_filter([$variantName, $colorName]);
            if (! empty($extras)) {
                $name .= ' ('.implode(' / ', $extras).')';
            }

            $unitPrice = (float) ($item->unit_price ?? $item->price ?? 0);

            return [
                'id' => $item->id,
                'name' => $name,
                'sku' => $item->product?->sku ?? $item->variant?->sku ?? $item->legacy_sku ?? null,
                'quantity' => (int) $item->quantity,
                'unit_price' => $unitPrice,
                'total' => $unitPrice * (int) $item->quantity,
                'image' => $item->variant?->images?->first()?->url
                    ?? $item->product?->images?->first()?->url
                    ?? null,
            ];
        })->values()->all();

        return [
            'view_token' => $order->view_token,
            'order_number' => $order->order_number ?? (string) $order->id,
            'created_at' => optional($order->created_at)->toIso8601String(),
            'status' => [
                'value' => $order->status?->value ?? (string) $order->status,
                'label' => $order->status?->label() ?? null,
            ],
            'payment_status' => [
                'value' => $order->payment_status?->value ?? (string) $order->payment_status,
                'label' => $order->payment_status?->label() ?? null,
            ],
            'payment_method' => $order->payment_method,
            'total_amount' => (float) $order->total_amount,
            'discount_amount' => (float) $order->discount_amount
                + (float) $order->total_promo_discount
                + (float) $order->total_items_discount,
            'delivery_cost' => (float) $order->delivery_cost,
            'delivery_method' => $deliveryMethodLabel,
            'delivery_target' => $deliveryTargetLabel,
            'delivery_address' => $deliveryAddressText,
            'recipient' => [
                'name' => $recipientName,
                'phone' => $recipientPhone,
                'email' => $order->client?->email,
            ],
            'items' => $items,
            'tracking_number' => $order->tracking_number,
        ];
    }
}
