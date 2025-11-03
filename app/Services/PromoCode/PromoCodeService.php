<?php

namespace App\Services\PromoCode;

use App\Models\Client;
use App\Models\Discountable;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\PromoCode;
use App\Traits\ProductsTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PromoCodeService
{
    use ProductsTrait;
    public function validatePromo(Request $request): array
    {
        $code = $request->get('code');
        $clientId = $request->get('client_id');
        $productIds = $request->get('product_ids') ?? [];

        $client = $this->resolveClient($request, $clientId);
        if ($client instanceof \Illuminate\Http\JsonResponse) {
            return ['error' => $client];
        }

        $promoCode = PromoCode::where('code', $code)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->first();

        if (!$promoCode) {
            return ['error' => response()->json(['message' => 'Промокод не найден или истёк'], 404)];
        }

        if (!$this->isAvailableForClient($promoCode, $client->id)) {
            return ['error' => response()->json(['message' => 'Промокод недоступен этому клиенту'], 400)];
        }


        if ($promoCode->max_uses && $promoCode->times_used >= $promoCode->max_uses) {
            return ['error' => response()->json(['message' => 'Лимит использований промокода исчерпан'], 400)];
        }

        if ($promoCode->usages()->where('client_id', $client->id)->exists()) {
            return ['error' => response()->json(['message' => 'Вы уже использовали этот промокод'], 400)];
        }


        if (empty($productIds) && $promoCode->type === 'specific') {
            return ['error' => response()->json([
                'message' => 'Промокод предназначен для конкретных товаров',
                'promo_code' => $promoCode,
            ], 400)];
        }

        $notApplicable = [];
        $applicable = [];


        foreach ($productIds as $productId => $variantIds) {
            $variantIds = is_array($variantIds) ? $variantIds : [$variantIds];

            foreach ($variantIds as $variantId) {
                $variantId = $variantId ?: null;

                if ($this->isAvailableForProduct($promoCode, $productId, $variantId)) {

                    $applicable[] = [
                        'product_id' => (int)$productId,
                        'variant_id' => $variantId ? (int)$variantId : null,
                    ];

                } else {
                    $notApplicable[] = [
                        'product_id' => (int)$productId,
                        'variant_id' => $variantId ? (int)$variantId : null,
                    ];
                }
            }
        }


        if (empty($applicable)) {
            return ['error' => response()->json([
                'message' => 'Промокод не применяется ни к одному из выбранных товаров',
                'not_applicable_products' => $notApplicable,
                'promo_code' => $promoCode,
            ], 400)];
        }



        $applicableWithPrices = $this->calculateProductsPrices($applicable, $promoCode);
        $notApplicableWithPrices = $this->calculateProductsPrices($notApplicable, null);



        if (!empty($notApplicable)) {
            return [
                'success' => true,
                'message' => 'Промокод применён не ко всем товарам',
                'promo_code' => [
                    'id' => $promoCode->id,
                    'code' => $promoCode->code,
                    'discount_type' => $promoCode->discount_type,
                    'discount_amount' => $promoCode->discount_amount,
                    'discount_behavior' => $promoCode->discount_behavior,
                    'description' => $promoCode->description,
                ],
                'applicable_products' => $applicableWithPrices,
                'not_applicable_products' => $notApplicableWithPrices,
            ];
        }

        return [
            'success' => true,
            'message' => 'Промокод применён ко всем выбранным товарам',
            'promo_code' => [
                'id' => $promoCode->id,
                'code' => $promoCode->code,
                'discount_type' => $promoCode->discount_type,
                'discount_amount' => $promoCode->discount_amount,
                'discount_behavior' => $promoCode->discount_behavior,
                'description' => $promoCode->description,
            ],
            'applicable_products' => $applicableWithPrices,
        ];



//        if (!empty($notApplicable)) {
//
//            return [
//                'success' => true,
//                'message' => 'Промокод применён не ко всем товарам',
//                'promo_code' => $promoCode,
//                'applicable_products' => $applicable,
//                'not_applicable_products' => $notApplicable,
//            ];
//        }
//
//        return [
//            'success' => true,
//            'message' => 'Промокод применён ко всем выбранным товарам',
//            'promo_code' => $promoCode,
//            'applicable_products' => $applicable,
//        ];
    }



    /**
     * Рассчитать цены для списка продуктов с учетом скидок и промокода
     */
    private function calculateProductsPrices(array $products, ?PromoCode $promoCode = null): array
    {
        $result = [];

        foreach ($products as $item) {
            $productId = $item['product_id'];
            $variantId = $item['variant_id'] ?? null;

            // Загружаем продукт или вариант
            if ($variantId) {
                $model = ProductVariant::with('product')->find($variantId);
                $isVariant = true;
            } else {
                $model = Product::find($productId);
                $isVariant = false;
            }

            if (!$model) {
                continue;
            }

            // Сохраняем исходные данные
            $originalPrice = $model->price;
            $originalOldPrice = $model->old_price;

            // Шаг 1: Применяем обычную скидку продукта
            $this->applyDiscountToProduct($model);

            $priceAfterDiscount = $model->price;
            $discountInfo = [
                'has_discount' => $model->discount_id !== null || ($model->old_price && $model->old_price > $model->price),
                'discount_id' => $model->discount_id ?? null,
                'discount_percentage' => $model->discount_percentage ?? null,
                'total_discount' => $model->total_discount ?? null,
            ];

            // Шаг 2: Применяем промокод, если есть
            $promoInfo = null;
            if ($promoCode) {
                $this->applyPromoCodeToProduct($model, $promoCode);

                if ($model->promo_code_applicable ?? false) {
                    $promoInfo = [
                        'promo_code_id' => $model->promo_code_id ?? null,
                        'promo_discount' => $model->promo_code_discount ?? 0,
                        'price_with_promo' => $model->price_with_promo ?? $priceAfterDiscount,
                        'behavior' => $promoCode->discount_behavior,
                        'original_discount_replaced' => $model->original_discount_replaced ?? false,
                        'price_before_promo' => $model->price_before_promo ?? null,
                    ];
                }
            }

            // Формируем результат
            $productData = [
                'product_id' => $productId,
                'variant_id' => $variantId,
                'name' => $model->name,
                'is_variant' => $isVariant,

                // Цены
                'original_price' => (float)$originalPrice,
                'old_price' => $model->old_price ? (float)$model->old_price : null,
                'price_after_discount' => (float)$priceAfterDiscount,
                'final_price' => $promoInfo ? (float)$promoInfo['price_with_promo'] : (float)$priceAfterDiscount,

                // Информация о скидке продукта
                'discount' => $discountInfo,

                // Информация о промокоде
                'promo_code_applied' => $promoInfo !== null,
                'promo_code_info' => $promoInfo,

                // Расчет экономии
                'savings' => $this->calculateSavings(
                    $originalPrice,
                    $priceAfterDiscount,
                    $promoInfo ? $promoInfo['price_with_promo'] : $priceAfterDiscount,
                    $discountInfo,
                    $promoInfo
                ),
            ];

            $result[] = $productData;
        }

        return $result;
    }

    /**
     * Рассчитать общую экономию
     */
    private function calculateSavings(
        float $originalPrice,
        float $priceAfterDiscount,
        float $finalPrice,
        array $discountInfo,
        ?array $promoInfo
    ): array {
        $totalSavings = $originalPrice - $finalPrice;
        $discountSavings = $discountInfo['total_discount'] ?? 0;
        $promoSavings = $promoInfo['promo_discount'] ?? 0;

        // Рассчитываем процент общей экономии
        $savingsPercentage = $originalPrice > 0
            ? round(($totalSavings / $originalPrice) * 100, 2)
            : 0;

        return [
            'total_savings' => round($totalSavings, 2),
            'discount_savings' => round($discountSavings, 2),
            'promo_savings' => round($promoSavings, 2),
            'savings_percentage' => $savingsPercentage,
        ];
    }


    private function isAvailableForProduct(PromoCode $promoCode, ?int $productId, ?int $variantId = null): bool
    {

        if ($promoCode->type === 'all' || $promoCode->applies_to_all_products) {
            return true;
        }

        if ($promoCode->type === 'specific') {
            if (!$productId) {
                return false;
            }

            // Если у продукта есть вариант — проверяем и его
            $query = $promoCode->products()->where('product_id', $productId);

            if ($variantId) {
                $query->where(function ($q) use ($variantId) {
                    $q->whereNull('product_variant_id')
                        ->orWhere('product_variant_id', $variantId);
                });
            }

            return $query->exists();
        }

        return false;
    }


    /**
     * Проверяет, доступен ли промокод конкретному клиенту.
     */
    private function isAvailableForClient(PromoCode $promoCode, int $clientId): bool
    {

        if ($promoCode->applies_to_all_clients) {
            return true;
        }

        if ($promoCode->clients()->exists()) {
            return $promoCode->clients()->where('client_id', $clientId)->exists();
        }

        return false;
    }

    /**
     * Определяет клиента на основе client_id или текущего авторизованного пользователя.
     */
    private function resolveClient(Request $request, ?int $clientId)
    {
        if ($clientId) {
            return Client::find($clientId);
        }

        $user = $request->user();

        if ($user instanceof Client) {
            return $user;
        }

        if ($user instanceof \App\Models\User) {
            return response()->json([
                'success' => false,
                'message' => 'Пожалуйста, укажите client_id — вы авторизованы как администратор.',
            ], 422);
        }

        return response()->json([
            'success' => false,
            'message' => 'Пользователь не авторизован.',
        ], 401);
    }
}
