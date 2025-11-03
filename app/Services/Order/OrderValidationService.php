<?php

namespace App\Services\Order;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\PromoCode;
use App\Traits\ProductsTrait;
use Illuminate\Support\Facades\Log;

class OrderValidationService
{
    use ProductsTrait;

    public function validateOrderItems(array $items, ?PromoCode $promoCode = null): array
    {
        Log::info('=== START ORDER VALIDATION ===');
        Log::info('Items count: ' . count($items));
        Log::info('PromoCode: ' . ($promoCode ? $promoCode->code : 'null'));

        $errors = [];
        $validatedItems = [];

        foreach ($items as $index => $item) {
            Log::info("Validating item #{$index}", [
                'product_id' => $item['product_id'] ?? null,
                'variant_id' => $item['product_variant_id'] ?? null,
                'price' => $item['price'] ?? null,
            ]);

            try {
                $result = $this->validateSingleItem($item, $index, $promoCode);

                if ($result['error']) {
                    Log::warning("Item #{$index} validation failed", $result['error']);
                    $errors[] = $result['error'];
                } else {
                    Log::info("Item #{$index} validation passed");
                    $validatedItems[] = $result['validated_item'];
                }
            } catch (\Exception $e) {
                Log::error("Exception in validateSingleItem for item #{$index}", [
                    'error' => $e->getMessage(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ]);

                $errors[] = [
                    'item' => $index,
                    'message' => 'Ошибка валидации товара: ' . $e->getMessage(),
                    'code' => 'VALIDATION_ERROR',
                ];
            }
        }

        Log::info('=== END ORDER VALIDATION ===', [
            'valid' => empty($errors),
            'errors_count' => count($errors),
            'validated_items_count' => count($validatedItems),
        ]);

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'validated_items' => $validatedItems,
        ];
    }

    private function validateSingleItem(array $item, int $index, ?PromoCode $promoCode = null): array
    {
        $productId = $item['product_id'];
        $variantId = $item['product_variant_id'] ?? null;
        $frontendPrice = (float)$item['price'];
        $quantity = (int)$item['quantity'];
        $colorId = $item['color_id'] ?? null;

        Log::info("Step 1: Loading product model", [
            'product_id' => $productId,
            'variant_id' => $variantId,
        ]);

        // 1. Загрузка модели товара или варианта
        $modelResult = $this->loadProductModel($productId, $variantId);
        if (!$modelResult['success']) {
            Log::warning("Product model not loaded", $modelResult);
            return [
                'error' => [
                    'item' => $index,
                    'product_id' => $productId,
                    'variant_id' => $variantId,
                    'message' => $modelResult['message'],
                    'code' => 'PRODUCT_NOT_FOUND',
                ],
                'validated_item' => null,
            ];
        }

        $model = $modelResult['model'];
        Log::info("Product model loaded", [
            'model_type' => get_class($model),
            'model_id' => $model->id,
            'name' => $model->name,
        ]);

        // 2. Проверка активности
        Log::info("Step 2: Checking active status");
        $activeCheck = $this->checkProductActive($model, $index);
        if ($activeCheck) {
            return ['error' => $activeCheck, 'validated_item' => null];
        }

        // 3. Проверка остатков
        Log::info("Step 3: Checking stock_quantity");
        $stockCheck = $this->checkProductStock($model, $quantity, $index);
        if ($stockCheck) {
            return ['error' => $stockCheck, 'validated_item' => null];
        }

        // 4. Проверка минимального количества
        Log::info("Step 4: Checking min quantity");
        $minQtyCheck = $this->checkMinimumQuantity($model, $quantity, $index);
        if ($minQtyCheck) {
            return ['error' => $minQtyCheck, 'validated_item' => null];
        }

        // 5. Проверка максимального количества
        Log::info("Step 5: Checking max quantity");
        $maxQtyCheck = $this->checkMaximumQuantity($model, $quantity, $index);
        if ($maxQtyCheck) {
            return ['error' => $maxQtyCheck, 'validated_item' => null];
        }

        // 6. Расчет цен с учетом скидок и промокода
        Log::info("Step 6: Calculating prices");
        try {
            $priceData = $this->calculateItemPrice($model, $productId, $variantId, $promoCode);
            Log::info("Price calculated", $priceData);
        } catch (\Exception $e) {
            Log::error("Error calculating price", [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);
            throw $e;
        }

        // 7. Проверка соответствия цены
        Log::info("Step 7: Checking price match");
        $priceCheck = $this->checkPriceMatch(
            $frontendPrice,
            $priceData['final_price'],
            $model,
            $index,
            $productId,
            $variantId,
            $priceData
        );

        if ($priceCheck) {
            return ['error' => $priceCheck, 'validated_item' => null];
        }

        // 8. Формируем валидированную позицию
        Log::info("Step 8: Item validated successfully");
        return [
            'error' => null,
            'validated_item' => [
                'product_id' => $productId,
                'variant_id' => $variantId,
                'color_id' => $colorId,
                'quantity' => $quantity,
                'original_price' => $priceData['original_price'],
                'price_after_discount' => $priceData['price_after_discount'],
                'final_price' => $priceData['final_price'],
                'discount_amount' => $priceData['discount_amount'],
                'promo_discount' => $priceData['promo_discount'],
                'model' => $model,
                'name' => $model->name,
            ],
        ];
    }

    private function loadProductModel(int $productId, ?int $variantId): array
    {
        try {
            if ($variantId) {
                Log::info("Loading ProductVariant with relations");

                // Явно указываем, какие связи загружать
                $model = ProductVariant::with([
                    'product',
                    'unit',
                    'colors',
                ])->find($variantId);

                if (!$model) {
                    return [
                        'success' => false,
                        'message' => "Вариант товара #{$variantId} не найден",
                    ];
                }

                if ($model->product_id != $productId) {
                    return [
                        'success' => false,
                        'message' => "Вариант #{$variantId} не принадлежит продукту #{$productId}",
                    ];
                }

                // Проверяем, что discount() метод существует
                if (!method_exists($model, 'discount')) {
                    Log::warning("ProductVariant model doesn't have discount() method");
                }

                return ['success' => true, 'model' => $model];
            }

            Log::info("Loading Product with relations");

            $model = Product::with([
                'defaultUnit',
                'colors',
            ])->find($productId);

            if (!$model) {
                return [
                    'success' => false,
                    'message' => "Товар #{$productId} не найден",
                ];
            }

            // Проверяем, что discount() метод существует
            if (!method_exists($model, 'discount')) {
                Log::warning("Product model doesn't have discount() method");
            }

            return ['success' => true, 'model' => $model];

        } catch (\Exception $e) {
            Log::error("Error loading product model", [
                'product_id' => $productId,
                'variant_id' => $variantId,
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
            ]);

            return [
                'success' => false,
                'message' => "Ошибка загрузки товара: " . $e->getMessage(),
            ];
        }
    }

    private function checkProductActive($model, int $index): ?array
    {
        if (!$model->is_active) {
            return [
                'item' => $index,
                'message' => "Товар '{$model->name}' недоступен для заказа",
                'code' => 'PRODUCT_INACTIVE',
            ];
        }
        return null;
    }

    private function checkProductStock($model, int $quantity, int $index): ?array
    {

        Log::info([
            'product' => $model->toArray(),
        ]);

        $stockQuantity = $model->stock_quantity ?? 0;

        if ($stockQuantity < $quantity) {
            return [
                'item' => $index,
                'message' => "Недостаточно товара '{$model->name}' на складе",
                'available' => $stockQuantity,
                'requested' => $quantity,
                'code' => 'INSUFFICIENT_STOCK',
            ];
        }
        return null;
    }

    private function checkMinimumQuantity($model, int $quantity, int $index): ?array
    {
        if ($model->min_order_quantity && $quantity < $model->min_order_quantity) {
            return [
                'item' => $index,
                'message' => "Минимальное количество для заказа товара '{$model->name}': {$model->min_order_quantity}",
                'min_quantity' => $model->min_order_quantity,
                'code' => 'MIN_QUANTITY_NOT_MET',
            ];
        }
        return null;
    }

    private function checkMaximumQuantity($model, int $quantity, int $index): ?array
    {
        if ($model->max_order_quantity && $quantity > $model->max_order_quantity) {
            return [
                'item' => $index,
                'message' => "Максимальное количество для заказа товара '{$model->name}': {$model->max_order_quantity}",
                'max_quantity' => $model->max_order_quantity,
                'code' => 'MAX_QUANTITY_EXCEEDED',
            ];
        }
        return null;
    }

    private function calculateItemPrice($model, int $productId, ?int $variantId, ?PromoCode $promoCode): array
    {
        Log::info("Calculating price", [
            'original_price' => $model->price,
            'has_promo' => $promoCode !== null,
        ]);

        $originalPrice = $model->price;
        $originalOldPrice = $model->old_price;

        // Применяем скидку товара
        Log::info("Applying product discount");
        try {
            $this->applyDiscountToProduct($model);
        } catch (\Exception $e) {
            Log::error("Error applying discount to product", [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }

        $priceAfterDiscount = $model->price;
        $discountAmount = $originalPrice - $priceAfterDiscount;

        $finalPrice = $priceAfterDiscount;
        $promoDiscount = 0;
        $promoApplied = false;

        // Применяем промокод если есть
        if ($promoCode) {
            Log::info("Applying promo code");
            try {
                $promoResult = $this->applyPromoCodeToItem($model, $promoCode, $productId, $variantId);

                if ($promoResult['applied']) {
                    $finalPrice = $promoResult['final_price'];
                    $promoDiscount = $promoResult['promo_discount'];
                    $promoApplied = true;
                }
            } catch (\Exception $e) {
                Log::error("Error applying promo code", [
                    'error' => $e->getMessage(),
                    'line' => $e->getLine(),
                ]);
                throw $e;
            }
        }

        return [
            'original_price' => round($originalPrice, 2),
            'price_after_discount' => round($priceAfterDiscount, 2),
            'final_price' => round($finalPrice, 2),
            'discount_amount' => round($discountAmount, 2),
            'promo_discount' => round($promoDiscount, 2),
            'promo_applied' => $promoApplied,
            'has_discount' => $model->discount_id !== null,
        ];
    }

    private function applyPromoCodeToItem($model, PromoCode $promoCode, int $productId, ?int $variantId): array
    {
        if (!$this->isPromoApplicableToProduct($promoCode, $productId, $variantId)) {
            return [
                'applied' => false,
                'final_price' => $model->price,
                'promo_discount' => 0,
            ];
        }

        $this->applyPromoCodeToProduct($model, $promoCode);

        if ($model->promo_code_applicable ?? false) {
            return [
                'applied' => true,
                'final_price' => $model->price_with_promo ?? $model->price,
                'promo_discount' => $model->promo_code_discount ?? 0,
            ];
        }

        return [
            'applied' => false,
            'final_price' => $model->price,
            'promo_discount' => 0,
        ];
    }

    private function isPromoApplicableToProduct(PromoCode $promoCode, int $productId, ?int $variantId): bool
    {
        if ($promoCode->type === 'all' || $promoCode->applies_to_all_products) {
            return true;
        }

        if ($promoCode->type === 'specific') {
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

    private function checkPriceMatch(
        float $frontendPrice,
        float $calculatedPrice,
              $model,
        int   $index,
        int   $productId,
        ?int  $variantId,
        array $priceData
    ): ?array
    {
        $priceDifference = abs($calculatedPrice - $frontendPrice);

        if ($priceDifference > 0.01) {
            return [
                'item' => $index,
                'product_id' => $productId,
                'variant_id' => $variantId,
                'name' => $model->name,
                'message' => "Цена товара '{$model->name}' изменилась. Пожалуйста, обновите корзину.",
                'frontend_price' => round($frontendPrice, 2),
                'actual_price' => round($calculatedPrice, 2),
                'original_price' => round($priceData['original_price'], 2),
                'discount_applied' => $priceData['has_discount'],
                'promo_applied' => $priceData['promo_applied'],
                'price_difference' => round($priceDifference, 2),
                'code' => 'PRICE_MISMATCH',
            ];
        }

        return null;
    }

    public function calculateOrderTotals(array $validatedItems): array
    {
        $orderTotal = 0;
        $totalDiscount = 0;
        $totalPromoDiscount = 0;
        $itemsCount = 0;

        foreach ($validatedItems as $item) {
            $quantity = $item['quantity'];
            $subtotal = $item['final_price'] * $quantity;
            $discountAmount = $item['discount_amount'] * $quantity;
            $promoDiscount = $item['promo_discount'] * $quantity;

            $orderTotal += $subtotal;
            $totalDiscount += $discountAmount;
            $totalPromoDiscount += $promoDiscount;
            $itemsCount += $quantity;
        }

        return [
            'order_total' => round($orderTotal, 2),
            'total_discount' => round($totalDiscount, 2),
            'total_promo_discount' => round($totalPromoDiscount, 2),
            'total_savings' => round($totalDiscount + $totalPromoDiscount, 2),
            'items_count' => $itemsCount,
        ];
    }
}
