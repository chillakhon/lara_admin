<?php

namespace App\Services\Order;

use App\Models\Order;
use App\Models\PromoCode;
use Illuminate\Support\Facades\Log;

class OrderCreationService
{
    /**
     * Создать заказ из валидированных данных
     *
     * @param array $orderData Данные заказа
     * @param int $clientId ID клиента
     * @return Order Созданный заказ
     */
    public function createOrder(array $orderData, int $clientId): Order
    {
        $order = Order::create([
            'client_id' => $clientId,
            'status' => Order::STATUS_NEW,
            'payment_status' => Order::PAYMENT_STATUS_PENDING,
            'order_number' => $this->generateOrderNumber(),
            'total_amount' => $orderData['total'] ?? 0,

            // Адрес доставки
            'country_code' => $orderData['country_code'] ?? null,
            'city_name' => $orderData['city_name'] ?? null,
//            'delivery_address' => $orderData['delivery_address'] ?? null,

            // Заметки
            'notes' => $orderData['notes'] ?? null,

            // Контактная информация
            'first_name' => $orderData['user']['first_name'] ?? null,
            'last_name' => $orderData['user']['last_name'] ?? null,
            'phone' => $orderData['user']['phone'] ?? null,

            // Временные метки
            'created_at' => now(),
        ]);


        return $order;
    }


    private function generateOrderNumber(): string
    {
        return 'ORD-' . strtoupper(uniqid());
    }

    /**
     * Создать позиции заказа из валидированных товаров
     *
     * @param Order $order Заказ
     * @param array $validatedItems Валидированные позиции
     * @return array Массив с итоговыми суммами
     */
    public function createOrderItems(Order $order, array $validatedItems)
    {


        try {


        $orderTotal = 0;
        $totalDiscount = 0;
        $totalPromoDiscount = 0;
        $itemsCreated = 0;


        foreach ($validatedItems as $item) {
            $quantity = $item['quantity'];
            $finalPrice = $item['final_price'];

            // Рассчитываем суммы для позиции
            $subtotal = $finalPrice * $quantity;
            $discountAmount = $item['discount_amount'] * $quantity;
            $promoDiscount = $item['promo_discount'] * $quantity;
            $totalItemDiscount = $discountAmount + $promoDiscount;

            // Создаем позицию заказа
            $orderItem = $order->items()->create([
                'order_id' => $order->id,
                'product_id' => $item['product_id'],
                'product_variant_id' => $item['variant_id'],
                'color_id' => $item['color_id'] ?? null,
                'quantity' => $quantity,
                'price' => $finalPrice,
                'discount' => $totalItemDiscount,
//                'subtotal' => $subtotal,
//                'name' => $item['name'] ?? null,
//                'original_price' => $item['original_price'],
            ]);

            // Уменьшаем остатки товара
//            $model = $item['model'];
//            $newQuantity = max(0, $model->stock_quantity - $quantity);
//            $model->update(['stock_quantity' => $newQuantity]);

            // Суммируем итоги
            $orderTotal += $subtotal;
            $totalDiscount += $discountAmount;
            $totalPromoDiscount += $promoDiscount;
            $itemsCreated++;


        }


        return [
            'order_total' => round($orderTotal, 2),
            'total_discount' => round($totalDiscount, 2),
            'total_promo_discount' => round($totalPromoDiscount, 2),
            'items_created' => $itemsCreated,
        ];


        } catch (\Exception $exception) {
            Log::error([
                'chilla' => 'chilla',
                'message' => $exception->getMessage(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ]);
        }


    }

    /**
     * Применить промокод к заказу и обновить суммы
     *
     * @param Order $order Заказ
     * @param PromoCode $promoCode Промокод
     * @param float $orderTotal Сумма заказа до скидок
     * @param float $totalDiscount Скидка от товаров
     * @param float $totalPromoDiscount Скидка от промокода
     * @return void
     */
    public function applyPromoCodeToOrder(
        Order     $order,
        PromoCode $promoCode,
        float     $orderTotal,
        float     $totalDiscount,
        float     $totalPromoDiscount
    ): void
    {
        // Общая скидка = скидка товаров + скидка промокода
        $totalDiscountAmount = $totalDiscount + $totalPromoDiscount;
        $totalAmountOriginal = $orderTotal + $totalDiscountAmount;

        // Обновляем заказ с информацией о промокоде и скидках
        $order->update([
            'promo_code_id' => $promoCode->id,
            'total_amount_original' => round($totalAmountOriginal, 2),
            'discount_amount' => round($totalDiscountAmount, 2),
            'total_promo_discount' => round($totalPromoDiscount, 2),
            'total_items_discount' => round($totalDiscount, 2),
        ]);

        // Создаем запись об использовании промокода
        $usage = $promoCode->usages()->create([
            'client_id' => $order->client_id,
            'order_id' => $order->id,
            'discount_amount' => round($totalPromoDiscount, 2), // ← используем существующее поле
        ]);

        // Увеличиваем счетчик использований промокода
        $promoCode->increment('max_uses');

        // Обновляем итоговую сумму заказа
        $order->updateTotalAmount();


    }

    /**
     * Обновить итоговые суммы заказа без промокода
     *
     * @param Order $order Заказ
     * @param array $totals Итоговые суммы
     * @return void
     */
    public function updateOrderTotals(Order $order, array $totals): void
    {
        $order->update([
            'discount_amount' => $totals['total_discount'] + ($totals['total_promo_discount'] ?? 0),
        ]);

        $order->updateTotalAmount();

    }

    /**
     * Отменить заказ и вернуть товары на склад
     *
     * @param Order $order Заказ
     * @param string $reason Причина отмены
     * @return bool Успешность операции
     */
    public function cancelOrder(Order $order, string $reason = null): bool
    {
        try {
            // Возвращаем товары на склад
            foreach ($order->items as $item) {
                if ($item->product_variant_id) {
                    $model = \App\Models\ProductVariant::find($item->product_variant_id);
                } else {
                    $model = \App\Models\Product::find($item->product_id);
                }

                if ($model) {
                    $model->increment('stock_quantity', $item->quantity);
                }
            }

            // Возвращаем использование промокода (если был применен)
            if ($order->promo_code_id) {
                $promoCode = PromoCode::find($order->promo_code_id);
                if ($promoCode) {
                    $promoCode->decrement('times_uses');

                    // Удаляем запись об использовании
                    $promoCode->usages()
                        ->where('order_id', $order->id)
                        ->delete();
                }
            }

            // Обновляем статус заказа
            $order->update([
                'status' => 'cancelled',
                'cancellation_reason' => $reason,
                'cancelled_at' => now(),
            ]);

            Log::info('Order cancelled', [
                'order_id' => $order->id,
                'reason' => $reason,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to cancel order', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Подтвердить заказ
     *
     * @param Order $order Заказ
     * @return bool Успешность операции
     */
    public function confirmOrder(Order $order): bool
    {
        try {
            $order->update([
                'status' => 'confirmed',
                'confirmed_at' => now(),
            ]);

            Log::info('Order confirmed', [
                'order_id' => $order->id,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to confirm order', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Пометить заказ как оплаченный
     *
     * @param Order $order Заказ
     * @param string $paymentMethod Метод оплаты
     * @param string|null $transactionId ID транзакции
     * @return bool Успешность операции
     */
    public function markAsPaid(Order $order, string $paymentMethod, ?string $transactionId = null): bool
    {
        try {
            $order->update([
                'payment_status' => 'paid',
                'payment_method' => $paymentMethod,
                'transaction_id' => $transactionId,
                'paid_at' => now(),
            ]);

            Log::info('Order marked as paid', [
                'order_id' => $order->id,
                'payment_method' => $paymentMethod,
                'transaction_id' => $transactionId,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to mark order as paid', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Изменить статус доставки заказа
     *
     * @param Order $order Заказ
     * @param string $status Новый статус
     * @return bool Успешность операции
     */
    public function updateDeliveryStatus(Order $order, string $status): bool
    {
        $allowedStatuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];

        if (!in_array($status, $allowedStatuses)) {
            Log::warning('Invalid delivery status', [
                'order_id' => $order->id,
                'status' => $status,
            ]);
            return false;
        }

        try {
            $order->update([
                'status' => $status,
                'updated_at' => now(),
            ]);

            if ($status === 'delivered') {
                $order->update(['delivered_at' => now()]);
            }

            Log::info('Order delivery status updated', [
                'order_id' => $order->id,
                'status' => $status,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to update delivery status', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Получить сводку по заказу
     *
     * @param Order $order Заказ
     * @return array Сводка
     */
    public function getOrderSummary(Order $order): array
    {
        $order->load(['items', 'client', 'promoCode']);

        $itemsCount = $order->items->sum('quantity');
        $subtotal = $order->items->sum(function ($item) {
            return $item->original_price * $item->quantity;
        });

        return [
            'order_id' => $order->id,
            'order_number' => $order->order_number ?? "ORD-{$order->id}",
            'status' => $order->status,
            'payment_status' => $order->payment_status,

            // Клиент
            'client' => [
                'id' => $order->client->id,
                'name' => $order->first_name . ' ' . $order->last_name,
                'email' => $order->client->email ?? null,
                'phone' => $order->phone,
            ],

            // Суммы
            'subtotal' => round($subtotal, 2),
            'discount_amount' => round($order->discount_amount, 2),
            'total_amount' => round($order->total_amount, 2),
            'items_count' => $itemsCount,

            // Промокод
            'promo_code' => $order->promoCode ? [
                'code' => $order->promoCode->code,
                'discount_type' => $order->promoCode->discount_type,
                'discount_amount' => $order->promoCode->discount_amount,
            ] : null,

            // Доставка
            'delivery' => [
                'country' => $order->country_code,
                'city' => $order->city_name,
                'address' => $order->delivery_address,
            ],

            // Даты
            'created_at' => $order->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $order->updated_at->format('Y-m-d H:i:s'),
            'confirmed_at' => $order->confirmed_at?->format('Y-m-d H:i:s'),
            'delivered_at' => $order->delivered_at?->format('Y-m-d H:i:s'),
        ];
    }
}
