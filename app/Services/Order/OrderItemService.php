<?php

namespace App\Services\Order;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\Log;

class OrderItemService
{
    protected OrderValidationService $validationService;
    protected OrderCreationService $creationService;

    public function __construct(
        OrderValidationService $validationService,
        OrderCreationService $creationService
    ) {
        $this->validationService = $validationService;
        $this->creationService = $creationService;
    }

    /**
     * Добавить позиции в заказ
     *
     * @param Order $order
     * @param array $items
     * @return array|null ['success' => bool, 'totals' => array|null, 'errors' => array|null]
     */
    public function addItems(Order $order, array $items): ?array
    {
        try {
            // Валидируем позиции
            $itemsValidation = $this->validationService->validateOrderItems(
                $items,
                $order->promoCode
            );

            if (!$itemsValidation['valid']) {
                return [
                    'success' => false,
                    'totals' => null,
                    'errors' => $itemsValidation['errors'],
                ];
            }

            // Добавляем позиции
            $totals = $this->creationService->createOrderItems(
                $order,
                $itemsValidation['validated_items']
            );

            // Обновляем суммы заказа
            $this->creationService->updateOrderTotals($order, $totals);

            Log::info('Items added to order', [
                'order_id' => $order->id,
                'items_count' => count($items),
            ]);

            return [
                'success' => true,
                'totals' => $totals,
                'errors' => null,
            ];

        } catch (\Exception $e) {
            Log::error('Failed to add items to order', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Удалить позицию из заказа
     *
     * @param Order $order
     * @param int $itemId
     * @return bool
     */
    public function removeItem(Order $order, int $itemId): bool
    {
        try {
            $item = $order->items()->findOrFail($itemId);

            // Проверяем что это не последняя позиция
            if ($order->items()->count() === 1) {
                Log::warning('Cannot remove last item from order', [
                    'order_id' => $order->id,
                    'item_id' => $itemId,
                ]);
                return false;
            }

            // Возвращаем товар на склад
            $this->returnItemToStock($item);

            // Удаляем позицию
            $item->delete();

            // Пересчитываем итоги
            $order->updateTotalAmount();

            Log::info('Item removed from order', [
                'order_id' => $order->id,
                'item_id' => $itemId,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to remove item from order', [
                'order_id' => $order->id,
                'item_id' => $itemId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Вернуть товар на склад
     *
     * @param OrderItem $item
     * @return void
     */
    protected function returnItemToStock(OrderItem $item): void
    {
        $model = $item->product_variant_id
            ? ProductVariant::find($item->product_variant_id)
            : Product::find($item->product_id);

        if ($model) {
            $model->increment('stock_quantity', $item->quantity);
        }
    }

    /**
     * Проверить можно ли изменять позиции заказа
     *
     * @param Order $order
     * @return bool
     */
    public function canModifyItems(Order $order): bool
    {
        return in_array($order->status, [
            OrderStatus::NEW,
            OrderStatus::PROCESSING,
        ]);
    }
}
