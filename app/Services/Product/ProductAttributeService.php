<?php

namespace App\Services\Product;

use App\Models\Product;

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
}
