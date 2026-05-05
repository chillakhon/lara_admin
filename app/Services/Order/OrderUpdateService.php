<?php

namespace App\Services\Order;

use App\Enums\OrderStatus;
use App\Models\Client;
use App\Models\DeliveryMethod;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderUpdateService
{
    /**
     * Обновить данные заказа
     */
    public function update(Order $order, array $data): bool
    {
        try {
            // Поля напрямую обновляемые в таблице orders
            $allowedFields = [
                'notes',
                'client_id',
                'status',
                'payment_status',
                'payment_method',
                'source',
                'delivery_method_id',
                'delivery_date',
                'delivery_comment',
            ];

            $filteredData = array_intersect_key(
                array_filter($data, fn ($value) => $value !== null && $value !== ''),
                array_flip($allowedFields)
            );

            // Определяем delivery_method_id по имени если пришёл объект delivery_method
            if (! isset($filteredData['delivery_method_id']) && isset($data['delivery_method']['name'])) {
                $method = DeliveryMethod::where('name', $data['delivery_method']['name'])->first();
                if ($method) {
                    $filteredData['delivery_method_id'] = $method->id;
                }
            }

            // Обновляем контактные данные клиента если переданы
            if (isset($data['user']) && is_array($data['user'])) {
                $this->updateClientContactInfo($order, $data['user']);
            }

            // Обработка адреса доставки
            if (isset($data['delivery_address'])) {
                // Если delivery_date есть на верхнем уровне, но нет в delivery_address — добавляем
                if (isset($data['delivery_date']) && ! isset($data['delivery_address']['delivery_date'])) {
                    $data['delivery_address']['delivery_date'] = $data['delivery_date'];
                }

                // Извлекаем delivery_date из delivery_address если есть
                if (array_key_exists('delivery_date', $data['delivery_address'])) {
                    $filteredData['delivery_date'] = $this->formatDeliveryDate($data['delivery_address']['delivery_date']);
                }

                $this->updateDeliveryAddress($order, $data['delivery_address']);
            }

            if (isset($data['items'])) {
                $this->updateOrderItems($order, $data['items']);
            }

            $order->update($filteredData);

            Log::info('Order updated', [
                'order_id' => $order->id,
                'updated_fields' => array_keys($filteredData),
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to update order', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Обновить контактную информацию клиента заказа
     */
    private function updateClientContactInfo(Order $order, array $userData): void
    {
        $clientId = $order->client_id;

        if (! $clientId) {
            return;
        }

        $client = Client::find($clientId);

        if (! $client) {
            return;
        }

        $updateData = array_filter([
            'first_name' => $userData['first_name'] ?? null,
            'last_name'  => $userData['last_name'] ?? null,
            'phone'      => $userData['phone'] ?? null,
        ], fn ($v) => $v !== null && $v !== '');

        if (! empty($updateData)) {
            $client->update($updateData);
        }
    }

    /**
     * Форматировать дату доставки
     */
    private function formatDeliveryDate($date): ?string
    {
        if (empty($date)) {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($date)->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            Log::warning('Failed to parse delivery_date', [
                'date' => $date,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Обновить адрес доставки заказа
     */
    private function updateDeliveryAddress(Order $order, array $addressData): void
    {
        $order->address()->updateOrCreate(
            ['order_id' => $order->id],
            [
                'country' => $addressData['country'] ?? null,
                'region' => $addressData['region'] ?? null,
                'city' => $addressData['city'] ?? null,
                'postal_code' => $addressData['postal_code'] ?? null,
                'address' => $addressData['address'] ?? null,
                'entrance' => $addressData['entrance'] ?? null,
                'floor' => $addressData['floor'] ?? null,
                'intercom' => $addressData['intercom'] ?? null,
                'delivery_comment' => $addressData['delivery_comment'] ?? null,
                'delivery_date' => $this->formatDeliveryDate($addressData['delivery_date'] ?? null),
                'buyer_comment' => $addressData['buyer_comment'] ?? null,
            ]
        );
    }

    /**
     * Обновить товары в заказе
     */
    private function updateOrderItems(Order $order, array $items): void
    {
        // Удаляем старые товары
        $order->items()->delete();

        // Создаем новые
        foreach ($items as $item) {
            $order->items()->create([
                'product_id' => $item['product_id'],
                'variant_id' => $item['variant_id'] ?? null,
                'product_variant_id' => $item['product_variant_id'] ?? null,
                'color_id' => $item['color_id'] ?? null,
                'quantity' => $item['quantity'],
                'price' => $item['price'],
            ]);
        }

        // Пересчитываем сумму заказа
        $total = $order->items()->sum(DB::raw('quantity * price'));
        $order->update(['total_amount' => $total]);
    }

    /**
     * Проверить можно ли редактировать заказ
     */
    public function canUpdate(Order $order): bool
    {
        // Можно редактировать заказы в любом статусе, кроме отмененных и доставленных
        return ! in_array($order->status, [
            OrderStatus::CANCELLED,
            OrderStatus::DELIVERED,
        ]);
    }
}
