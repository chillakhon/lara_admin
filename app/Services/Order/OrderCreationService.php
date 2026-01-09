<?php

namespace App\Services\Order;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\GiftCard\GiftCard;
use App\Models\Order;
use App\Models\PromoCode;
use App\Services\GiftCard\GiftCardService;
use Exception;
use Illuminate\Support\Facades\Log;

class OrderCreationService
{


    public function __construct(
        protected GiftCardService $giftCardService
    )
    {
    }

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
            'status' => OrderStatus::NEW,
            'payment_status' => PaymentStatus::PENDING,
            'order_number' => $this->generateOrderNumber(),
            'total_amount' => $orderData['total'] ?? 0,

            // Адрес доставки
            'country_code' => $orderData['country_code'] ?? null,
            'city_name' => $orderData['city_name'] ?? null,

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
                'status' => OrderStatus::CANCELLED,
                'cancellation_reason' => $reason,
                'cancelled_at' => now(),
            ]);


            $this->refundGiftCardOnCancellation($order);

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
     * Обновить статус заказа
     *
     * @param Order $order Заказ
     * @param OrderStatus $status Новый статус
     * @return bool Успешность операции
     */
    public function updateOrderStatus(Order $order, OrderStatus $status): bool
    {
        try {
            $order->update([
                'status' => $status,
                'updated_at' => now(),
            ]);

            // Специальные действия для определенных статусов
            if ($status === OrderStatus::DELIVERED) {
                $order->update(['delivered_at' => now()]);
            }

            Log::info('Order status updated', [
                'order_id' => $order->id,
                'status' => $status->value,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to update order status', [
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
                'id' => $order->client?->id,
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


    public function applyGiftCardToOrder(
        Order $order,
        GiftCard $giftCard,
        float $orderTotal
    ): array {
        try {
            // Применяем карту через сервис
            $result = $this->giftCardService->applyToOrder($giftCard, $order, $orderTotal);

            // Обновляем заказ
            $order->update([
                'gift_card_id' => $giftCard->id,
                'gift_card_amount' => $result['amount_used'],
                'total_amount' => $result['order_total_after'],
            ]);
//
//            Log::info('Gift card applied to order in OrderCreationService', [
//                'order_id' => $order->id,
//                'gift_card_id' => $giftCard->id,
//                'amount_used' => $result['amount_used'],
//            ]);

            return $result;

        } catch (Exception $e) {
            Log::error('Failed to apply gift card in OrderCreationService', [
                'order_id' => $order->id,
                'gift_card_id' => $giftCard->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Обновление сумм заказа с учетом подарочной карты
     */
    public function updateOrderTotalsWithGiftCard(
        Order $order,
        array $totals,
        ?float $giftCardAmount = null
    ): void {
        $orderTotal = $totals['order_total'];

        // Если есть подарочная карта, вычитаем её
        if ($giftCardAmount && $giftCardAmount > 0) {
            $orderTotal = max(0, $orderTotal - $giftCardAmount);
        }

        $order->update([
            'total_amount_original' => $totals['order_total'],
            'total_items_discount' => $totals['total_discount'],
            'total_amount' => $orderTotal,
        ]);
    }

    /**
     * Проверка: содержит ли заказ подарочный сертификат
     */
    public function containsGiftCardProduct(array $items): bool
    {
        foreach ($items as $item) {
            $product = \App\Models\Product::find($item['product_id']);

            if ($product && $product->name === 'Подарочный сертификат') {
                return true;
            }
        }

        return false;
    }

    /**
     * Извлечение номинала из варианта подарочного сертификата
     */
    public function extractGiftCardNominal(array $item): ?float
    {
        if (isset($item['product_variant_id'])) {
            $variant = \App\Models\ProductVariant::find($item['product_variant_id']);
            if ($variant) {
                return (float) $variant->price;
            }

        }

        return null;
    }

    /**
     * Возврат средств на подарочную карту при отмене заказа
     */
    public function refundGiftCardOnCancellation(Order $order): void
    {
        if (!$order->hasGiftCard()) {
            return;
        }

        try {
            $giftCard = $order->giftCard;

            if ($giftCard) {
                $this->giftCardService->refund(
                    $giftCard,
                    $order,
                    $order->gift_card_amount
                );

                Log::info('Gift card refunded on order cancellation', [
                    'order_id' => $order->id,
                    'gift_card_id' => $giftCard->id,
                    'refund_amount' => $order->gift_card_amount,
                ]);
            }

        } catch (Exception $e) {
            Log::error('Failed to refund gift card on cancellation', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            // Не выбрасываем исключение, чтобы не блокировать отмену заказа
        }
    }

}
