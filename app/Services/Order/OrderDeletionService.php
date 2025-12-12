<?php

namespace App\Services\Order;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Support\Facades\Log;

class OrderDeletionService
{
    protected OrderCreationService $orderCreationService;

    public function __construct(OrderCreationService $orderCreationService)
    {
        $this->orderCreationService = $orderCreationService;
    }

    /**
     * Удалить заказ (мягкое удаление)
     *
     * @param Order $order
     * @param string|null $reason
     * @return bool
     */
    public function delete(Order $order, ?string $reason = null): bool
    {
        try {
            // Если заказ активный - сначала отменяем
            if ($order->status !== OrderStatus::CANCELLED) {
                $cancelSuccess = $this->orderCreationService->cancelOrder(
                    $order,
                    $reason ?? 'Удалён администратором'
                );

                if (!$cancelSuccess) {
                    return false;
                }
            }

            // Мягкое удаление
            $order->delete();

            Log::info('Order deleted', [
                'order_id' => $order->id,
                'reason' => $reason,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to delete order', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Проверить можно ли удалить заказ
     *
     * @param Order $order
     * @return bool
     */
    public function canDelete(Order $order): bool
    {
        // Нельзя удалить заказы в процессе обработки, отправленные или доставленные
        return !in_array($order->status, [
            OrderStatus::PROCESSING,
            OrderStatus::SHIPPED,
            OrderStatus::DELIVERED,
        ]);
    }
}
