<?php

namespace App\Services\Product;

use App\Models\Product;
use Illuminate\Support\Facades\DB;

class ProductOrderService
{
    /**
     * Изменить порядок отображения товара и пересчитать порядки других товаров
     *
     * @param int $productId - ID товара, который перемещаем
     * @param int $newOrder - Новый порядок товара
     * @return array - Массив с информацией об обновленных товарах
     */
    public function updateProductOrder(int $productId, int $newOrder): array
    {
        DB::beginTransaction();

        try {
            $product = Product::findOrFail($productId);
            $oldOrder = $product->display_order;

            // Если новый порядок совпадает со старым, ничего не делаем
            if ($oldOrder === $newOrder) {
                return [
                    'success' => true,
                    'message' => 'Порядок не изменился',
                    'updated_products' => []
                ];
            }

            // Если новый порядок меньше старого, сдвигаем товары вверх
            if ($newOrder < $oldOrder) {
                Product::whereBetween('display_order', [$newOrder, $oldOrder - 1])
                    ->where('id', '!=', $productId)
                    ->where('is_active', true)
                    ->whereNull('deleted_at')
                    ->increment('display_order');
            }
            // Если новый порядок больше старого, сдвигаем товары вниз
            else {
                Product::whereBetween('display_order', [$oldOrder + 1, $newOrder])
                    ->where('id', '!=', $productId)
                    ->where('is_active', true)
                    ->whereNull('deleted_at')
                    ->decrement('display_order');
            }

            // Обновляем порядок целевого товара
            $product->update(['display_order' => $newOrder]);

            DB::commit();

            return [
                'success' => true,
                'message' => "Порядок товара успешно изменен с {$oldOrder} на {$newOrder}",
                'product_id' => $productId,
                'old_order' => $oldOrder,
                'new_order' => $newOrder
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            return [
                'success' => false,
                'message' => 'Ошибка при изменении порядка товара',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Получить максимальный порядок среди активных товаров
     *
     * @return int
     */
    public function getMaxOrder(): int
    {
        return Product::where('is_active', true)
            ->whereNull('deleted_at')
            ->max('display_order') ?? 0;
    }

    /**
     * Инициализировать порядок для всех активных товаров
     * Используется если в базе есть старые товары без порядка
     *
     * @return array
     */
    public function initializeAllProductOrders(): array
    {
        DB::beginTransaction();

        try {
            $products = Product::where('is_active', true)
                ->whereNull('deleted_at')
                ->orderBy('created_at', 'asc')
                ->get();

            $order = 1;
            foreach ($products as $product) {
                $product->update(['display_order' => $order]);
                $order++;
            }

            DB::commit();

            return [
                'success' => true,
                'message' => "Порядок инициализирован для {$products->count()} товаров",
                'count' => $products->count()
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            return [
                'success' => false,
                'message' => 'Ошибка при инициализации порядка',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Пакетное обновление порядка товаров
     *
     * @param array $orders - Массив вида ['product_id' => order, ...]
     * @return array
     */
    public function bulkUpdateOrders(array $orders): array
    {
        DB::beginTransaction();

        try {
            $updatedCount = 0;

            foreach ($orders as $productId => $newOrder) {
                $product = Product::findOrFail($productId);

                // Валидация - порядок должен быть положительным числом
                if (!is_numeric($newOrder) || $newOrder < 1) {
                    throw new \Exception("Порядок товара {$productId} должен быть положительным числом");
                }

                $product->update(['display_order' => $newOrder]);
                $updatedCount++;
            }

            DB::commit();

            return [
                'success' => true,
                'message' => "Порядок успешно обновлен для {$updatedCount} товаров",
                'updated_count' => $updatedCount
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            return [
                'success' => false,
                'message' => 'Ошибка при пакетном обновлении порядка',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Перестроить порядок товаров без пробелов (выравнивание)
     *
     * @return array
     */
    public function rebuildProductOrders(): array
    {
        DB::beginTransaction();

        try {
            $products = Product::where('is_active', true)
                ->whereNull('deleted_at')
                ->orderBy('display_order', 'asc')
                ->get();

            $order = 1;
            foreach ($products as $product) {
                $product->update(['display_order' => $order]);
                $order++;
            }

            DB::commit();

            return [
                'success' => true,
                'message' => "Порядок товаров перестроен. Обновлено {$products->count()} товаров",
                'count' => $products->count()
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            return [
                'success' => false,
                'message' => 'Ошибка при перестроении порядка',
                'error' => $e->getMessage()
            ];
        }
    }
}
