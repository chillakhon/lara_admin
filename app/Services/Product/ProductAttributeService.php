<?php

namespace App\Services\Product;

use App\Models\Product;
use Illuminate\Support\Facades\DB;

class ProductAttributeService
{
    /**
     * Обновить уровень впитываемости
     */
    public function updateAbsorbency(int $productId, int $absorbencyLevel): array
    {
        try {
            $product = Product::findOrFail($productId);

            $product->update(['absorbency_level' => $absorbencyLevel]);

            return [
                'success' => true,
                'message' => 'Уровень впитываемости обновлен',
                'data' => [
                    'id' => $product->id,
                    'absorbency_level' => $product->absorbency_level,
                ]
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Ошибка при обновлении: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Обновить несколько характеристик сразу
     */
    public function updateAttributes(int $productId, array $attributes): array
    {
        try {
            $product = Product::findOrFail($productId);

            $product->update($attributes);

            return [
                'success' => true,
                'message' => 'Характеристики обновлены',
                'data' => $product->only(array_keys($attributes))
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Ошибка при обновлении: ' . $e->getMessage(),
            ];
        }
    }


    public function bulkUpdateAttributes(array $productIds, array $attributes): array
    {
        DB::beginTransaction();

        try {
            // Проверяем существование всех товаров
            $products = Product::whereIn('id', $productIds)->get();

            if ($products->count() !== count($productIds)) {
                throw new \Exception('Один или несколько товаров не найдены');
            }

            // Обновляем все товары
            $updatedCount = Product::whereIn('id', $productIds)->update($attributes);

            DB::commit();

            return [
                'success' => true,
                'message' => "Обновлено товаров: {$updatedCount}",
                'data' => [
                    'updated_count' => $updatedCount,
                    'product_ids' => $productIds,
                    'attributes' => $attributes,
                ]
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            return [
                'success' => false,
                'message' => 'Ошибка при массовом обновлении: ' . $e->getMessage(),
            ];
        }
    }

}
