<?php

namespace App\Services\Order;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Support\Facades\Log;

class OrderUpdateService
{
    /**
     * Обновить данные заказа
     *
     * @param Order $order
     * @param array $data
     * @return bool
     */
    public function update(Order $order, array $data): bool
    {
        try {
            // Фильтруем только разрешенные поля
            $allowedFields = [
                'notes',
//                'country_code',
//                'city_name',
//                'delivery_address',
//                'first_name',
//                'last_name',
//                'phone',
//                'delivery_method_id',

                'status',
                'payment_status',
            ];

            $filteredData = array_intersect_key(
                array_filter($data),
                array_flip($allowedFields)
            );

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
     * Проверить можно ли редактировать заказ
     *
     * @param Order $order
     * @return bool
     */
    public function canUpdate(Order $order): bool
    {
        // Можно редактировать только заказы в определенных статусах
        return in_array($order->status, [
            OrderStatus::NEW,
            OrderStatus::PROCESSING,
        ]);
    }
}
