<?php

namespace App\Services\Promotion;

use App\Models\Order;
use App\Models\Promotion;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PromotionService
{
    /**
     * Найти применимые акции для корзины
     */
    public function findApplicablePromotions(array $cartItems, float $cartTotal): Collection
    {
        // Получаем активные акции
        $activePromotions = Promotion::where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', now());
            })
            ->where(function ($query) {
                $query->whereNull('max_uses')
                    ->orWhereRaw('times_used < max_uses');
            })
            ->with(['triggerProducts', 'giftProducts.images'])
            ->orderBy('priority', 'desc')
            ->get();

        $applicable = collect();

        foreach ($activePromotions as $promotion) {
            if ($this->isPromotionApplicable($promotion, $cartItems, $cartTotal)) {
                $applicable->push($promotion);
            }
        }

        return $applicable;
    }

    /**
     * Проверить, применима ли акция к корзине
     */
    protected function isPromotionApplicable(Promotion $promotion, array $cartItems, float $cartTotal): bool
    {
        // 1. Проверка минимальной суммы покупки
        if ($cartTotal < $promotion->min_purchase_amount) {
            return false;
        }

        // 2. Проверка наличия товаров-триггеров в корзине
        $triggerProductIds = $promotion->triggerProducts->pluck('id')->toArray();

        if (empty($triggerProductIds)) {
            // Если нет товаров-триггеров, акция применима ко всем
            return true;
        }

        $cartProductIds = collect($cartItems)->pluck('product_id')->toArray();

        // Проверяем, есть ли хотя бы один товар-триггер в корзине
        return ! empty(array_intersect($triggerProductIds, $cartProductIds));
    }

    /**
     * Применить акцию к заказу
     */
    public function applyPromotionToOrder(
        Order $order,
        Promotion $promotion,
        int $giftProductId,
        bool $useDiscountInstead = false
    ): void {
        DB::beginTransaction();

        try {
            // Обновляем заказ
            $order->update([
                'promotion_id' => $promotion->id,
            ]);

            if (! $useDiscountInstead) {
                // Добавляем подарок в заказ
                $giftProduct = $promotion->giftProducts()
                    ->where('product_id', $giftProductId)
                    ->first();

                if ($giftProduct) {
                    $quantity = $giftProduct->pivot->quantity ?? 1;

                    $order->items()->create([
                        'product_id' => $giftProductId,
                        'quantity' => $quantity,
                        'price' => 0.00, // Подарок бесплатный
                        'discount' => 0.00,
                        'is_gift' => true,
                        'promotion_id' => $promotion->id,
                    ]);
                }
            }

            // Создаем запись об использовании
            $promotion->usages()->create([
                'order_id' => $order->id,
                'client_id' => $order->client_id,
                'gift_product_id' => $giftProductId,
                'gift_quantity' => $giftProduct->pivot->quantity ?? 1,
                'used_discount_instead' => $useDiscountInstead,
            ]);

            // Увеличиваем счетчик использований
            $promotion->increment('times_used');

            DB::commit();

            Log::info('Promotion applied to order', [
                'promotion_id' => $promotion->id,
                'order_id' => $order->id,
                'gift_product_id' => $giftProductId,
                'used_discount_instead' => $useDiscountInstead,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to apply promotion to order', [
                'promotion_id' => $promotion->id,
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Проверить, можно ли использовать промокод с акцией
     */
    public function canUsePromoCodeWithPromotion(?Promotion $promotion): bool
    {
        if (! $promotion) {
            return true; // Нет акции - можно использовать промокод
        }

        return $promotion->allowsPromoCodes();
    }

    /**
     * Отменить применение акции к заказу
     */
    public function cancelPromotionFromOrder(Order $order): void
    {
        DB::beginTransaction();

        try {
            if (! $order->promotion_id) {
                return;
            }

            $promotion = Promotion::find($order->promotion_id);

            if ($promotion) {
                // Уменьшаем счетчик использований
                $promotion->decrement('times_used');

                // Удаляем запись об использовании
                $promotion->usages()
                    ->where('order_id', $order->id)
                    ->delete();
            }

            // Удаляем подарочные товары из заказа
            $order->items()
                ->where('is_gift', true)
                ->where('promotion_id', $order->promotion_id)
                ->delete();

            // Убираем акцию из заказа
            $order->update([
                'promotion_id' => null,
            ]);

            DB::commit();

            Log::info('Promotion cancelled from order', [
                'order_id' => $order->id,
                'promotion_id' => $promotion?->id,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to cancel promotion from order', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Получить статистику по акции
     */
    public function getPromotionStats(Promotion $promotion): array
    {
        return [
            'total_uses' => $promotion->times_used,
            'remaining_uses' => $promotion->max_uses ? ($promotion->max_uses - $promotion->times_used) : null,
            'total_orders' => $promotion->orders()->count(),
            'total_revenue' => $promotion->orders()->sum('total_amount'),
            'unique_clients' => $promotion->usages()->distinct('client_id')->count('client_id'),
            'gift_chosen_count' => $promotion->usages()->where('used_discount_instead', false)->count(),
            'discount_chosen_count' => $promotion->usages()->where('used_discount_instead', true)->count(),
        ];
    }
}
