<?php

namespace App\Services\Export;

use App\Helpers\DateHelper;
use App\Helpers\NumberHelper;
use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;

class OrderExportService extends ExportService
{
    /**
     * Получить заголовки CSV
     */
    protected function getHeaders(): array
    {
        return [
            'ID',
            'Номер заказа',
            'Клиент',
            'Email клиента',
            'Телефон клиента',
            'Статус заказа',
            'Статус оплаты',
            'Сумма заказа',
            'Скидка',
            'Промокод',
            'Подарочная карта',
            'Способ оплаты',
            'Способ доставки',
            'Адрес доставки',
            'Дата доставки',
            'Стоимость доставки',
            'Товары',
            'Примечания',
            'Дата создания',
            'Дата оплаты',
        ];
    }

    /**
     * Форматировать строку данных для CSV
     */
    protected function formatRow($order): array
    {
        // Формируем список товаров
        $items = $order->items
            ->map(function ($item) {
                $productName = $item->product->name ?? 'Неизвестный товар';
                $variantName = $item->variant ? " ({$item->variant->name})" : '';
                $quantity = $item->quantity;
                $price = NumberHelper::formatRussian($item->price * $item->quantity);

                return "{$productName}{$variantName} (x{$quantity}, {$price}₽)";
            })
            ->implode(', ');

        // Форматируем адрес доставки
        $deliveryAddress = $this->formatDeliveryAddress($order->delivery_address);

        // Получаем переведённые статусы
        $orderStatus = $order->status ? $order->status->label() : '';
        $paymentStatus = $order->payment_status ? $order->payment_status->label() : '';

        // Промокод
        $promoCode = $order->promoCode ? $order->promoCode->code : '';

        // Подарочная карта
        $giftCardAmount = $order->gift_card_amount > 0
            ? NumberHelper::formatRussian($order->gift_card_amount)
            : '';

        // Дата доставки из address или из order
        $deliveryDate = '';
        if ($order->address && $order->address->delivery_date) {
            $deliveryDate = DateHelper::formatRussian($order->address->delivery_date);
        } elseif ($order->delivery_date) {
            $deliveryDate = DateHelper::formatRussian($order->delivery_date);
        }

        return [
            $order->id,
            $order->order_number ?? '',
            $order->client->profile->full_name ?? '',
            $order->client->email ?? '',
            $order->client->profile->phone ?? '',
            $orderStatus,
            $paymentStatus,
            NumberHelper::formatRussian($order->total_amount),
            NumberHelper::formatRussian($order->discount_amount),
            $promoCode,
            $giftCardAmount,
            $order->payment_method ?? '',
            $order->deliveryMethod->name ?? '',
            $deliveryAddress,
            $deliveryDate,
            NumberHelper::formatRussian($order->delivery_cost),
            $items,
            $order->notes ?? '',
            DateHelper::formatRussian($order->created_at),
            DateHelper::formatRussian($order->paid_at),
        ];
    }

    /**
     * Получить query builder для выборки данных
     */
    protected function getQuery(array $ids = []): Builder
    {
        $query = Order::query()
            ->with([
                'client.profile',
                'items.product',
                'items.variant',
                'promoCode',
                'deliveryMethod',
                'address',
            ])
            ->whereNull('deleted_at');

        // Если переданы конкретные ID - фильтруем
        if (! empty($ids)) {
            $query->whereIn('id', $ids);
        }

        // Сортировка как в таблице (latest)
        $query->latest();

        return $query;
    }

    /**
     * Генерировать имя файла
     */
    protected function getFileName(): string
    {
        $timestamp = now()->format('Ymd_His');

        return "orders_{$timestamp}.csv";
    }

    /**
     * Форматировать адрес доставки из JSON
     */
    private function formatDeliveryAddress($deliveryAddress): string
    {
        if (empty($deliveryAddress)) {
            return '';
        }

        if (is_array($deliveryAddress)) {
            $entrance = ! empty($deliveryAddress['entrance']) ? 'подъезд '.$deliveryAddress['entrance'] : null;
            $floor = ! empty($deliveryAddress['floor']) ? 'этаж '.$deliveryAddress['floor'] : null;
            $intercom = ! empty($deliveryAddress['intercom']) ? 'домофон '.$deliveryAddress['intercom'] : null;

            return implode(', ', array_filter([
                $deliveryAddress['country'] ?? null,
                $deliveryAddress['region'] ?? null,
                $deliveryAddress['city'] ?? null,
                $deliveryAddress['postal_code'] ?? null,
                $deliveryAddress['address'] ?? null,
                $entrance,
                $floor,
                $intercom,
                $deliveryAddress['delivery_comment'] ?? null,
                $deliveryAddress['buyer_comment'] ?? null,
            ]));
        }

        if (is_string($deliveryAddress)) {
            $decodedAddress = json_decode($deliveryAddress, true);

            if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decodedAddress)) {
                return $deliveryAddress;
            }

            $deliveryAddress = $decodedAddress;
        }

        if (! is_array($deliveryAddress)) {
            return '';
        }

        // Собираем адрес из полей
        $parts = [];

        if (! empty($deliveryAddress['city'])) {
            $parts[] = $deliveryAddress['city'];
        }
        if (! empty($deliveryAddress['street'])) {
            $parts[] = $deliveryAddress['street'];
        }
        if (! empty($deliveryAddress['house'])) {
            $parts[] = "д. {$deliveryAddress['house']}";
        }
        if (! empty($deliveryAddress['apartment'])) {
            $parts[] = "кв. {$deliveryAddress['apartment']}";
        }

        return implode(', ', $parts);
    }
}
