<?php

namespace App\Traits;

use App\Models\InventoryBalance;
use App\Models\Product;
use App\Models\PromoCode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Laravel\Reverb\Loggers\Log;

trait ProductsTrait
{


    public function products_query(Request $request): Builder
    {

        $isAdmin = $request->boolean('admin', false);

        $sortBy = $request->get('sort_by', false);
        $sortOrder = $request->get('sort_order', 'asc');

        $products = Product
            ::with([
                'images' => function ($sql) {
                    $sql->orderBy("order", 'asc');
                },
                'colors:id,name,code',
                // 'options.values',
                // 'variants.optionValues.option',
                'variants' => function ($sql) {
                    $sql->whereNull("deleted_at")
                        ->with([
                            'unit',
                            'colors:id,name,code',
                            'images' => function ($sql) {
                                $sql->orderBy("order", 'asc');
                            }
                        ]);
                },
                'defaultUnit',
            ])
            ->withAvg([
                'reviews' => function ($query) {
                    $query->where('reviewable_type', Product::class)
                        ->where('is_published', true)
                        ->where('is_verified', true)
                        ->where('is_spam', false)
                        ->whereNull('deleted_at');
                }
            ], 'rating')
            ->when($request->get('search'), function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhereHas('categories', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('variants', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")->whereNull('deleted_at');
                    });
            })
            ->when($request->get('category_id'), function ($query, $categoryId) {
                $query->whereHas('categories', function ($q) use ($categoryId) {
                    $q->where('categories.id', $categoryId);
                });
            })

            // searching by color name
            ->when($request->get('color_name'), function ($query, $color_name) {
                $query->whereHas('colors', function ($sql) use ($color_name) {
                    $sql->where('colors.name', $color_name);
                })->orWhereHas('variants', function ($sql) use ($color_name) {
                    $sql->whereHas('colors', function ($sql2) use ($color_name) {
                        $sql2->where('colors.name', $color_name);
                    });
                });
            })
            ->when($request->get('color_id'), function ($query, $color_id) {
                $query->whereHas('variants', function ($q) use ($color_id) {
                    $q->whereNull('deleted_at')
                        ->where('color_id', $color_id); // фильтр прямо по variant.color_id
                });
            })
            ->when($request->filled('in_stock'), function ($query) use ($request) {
                if ($request->boolean('in_stock')) {
                    $query->where(function ($q) {
                        $q->whereHas('variants', fn($qv) => $qv->where('stock_quantity', '>', 0))
                            ->orWhere('stock_quantity', '>', 0);
                    });
                }
            })
            ->when(!$isAdmin, function ($query) {
                $query->where('is_active', true);
            })
            ->when($sortBy, function ($query) use ($sortBy, $sortOrder) {

                $query->orderBy($sortBy ?? 'id', $sortOrder ?? 'asc');
            });


        if (!$isAdmin) {
            $products
                ->orderByRaw('CASE WHEN stock_quantity > 0 THEN 0 ELSE 1 END')
                ->orderByRaw('CASE WHEN price > 0 THEN 0 ELSE 1 END');
        }

        $products->latest();


        if ($request->get('type', 'simple')) {
            $products->where('type', $request->get('type', 'simple'));
        }

        if ($request->get('product_id')) {
            $products->where('id', $request->get('product_id'));
        }
        if ($request->get('is_active')) {
            $products->where('is_active', $request->get('is_active'));
        }


        if ($request->filled('price_after')) {
            $products->where('price', '>=', (float)$request->input('price_after'));
        }

        if ($request->filled('price_before')) {
            $products->where('price', '<=', (float)$request->input('price_before'));
        }
//
//
//        if ($request->filled('sort_by')) {
//            $products->orderBy($request->get('sort_by'), $request->get('sort_order', 'asc'));
//        }

        return $products;
    }

    public function solve_products_inventory($products = [], $product_stock_sklad = [], $isAdmin = false)
    {
        foreach ($products as &$product) {
            if ($isAdmin && isset($product_stock_sklad[$product->uuid])) {
                // Если админ — берём "живые" остатки из MoySklad
                $product->inventory_balance = $product_stock_sklad[$product->uuid]['stock'] ?? 0.0;
            } else {
                // Если клиент — показываем данные из БД
                $product->inventory_balance = $product->stock_quantity ?? 0.0;
            }

            if (!empty($product['variants'])) {

                foreach ($product['variants'] as &$variant) {
                    if ($isAdmin && isset($product_stock_sklad[$variant->uuid])) {
                        $variant_total_qty = $product_stock_sklad[$variant->uuid]['stock'] ?? 0.0;
                    } else {
                        $variant_total_qty = $variant->stock_quantity ?? 0.0;
                    }

                    $variant->inventory_balance = $variant_total_qty;
                    $product->inventory_balance += $variant_total_qty;
                }
            }
        }
    }


    // FOR SOLVING DISCOUNTS
    public function applyDiscountsToCollection(Collection $products): void
    {
        foreach ($products as $product) {
            $this->applyDiscountToProduct($product);


            if ($products)

                if ($product->relationLoaded('variants')) {
                    foreach ($product->variants as $variant) {
                        $this->applyDiscountToProduct($variant);
                    }
                }

        }
    }

    public function applyDiscountToProduct($model): void
    {


        if ($model->name === 'Подарочный сертификат') {
            $model->discount_id = null;
            $model->discount_percentage = null;
            $model->total_discount = null;
            return;
        }

        if ($model->product_id) {
            $parentProduct = Product::find($model->product_id);
            if ($parentProduct && $parentProduct->name === 'Подарочный сертификат') {
                $model->discount_id = null;
                $model->discount_percentage = null;
                $model->total_discount = null;
                return;
            }
        }


        // Support both Product and ProductVariant
        $price = $model->price;
        $oldPrice = $model->old_price;
        $discount = $model->discount();
        // $model->tempHEHEHE = $discount ? $discount : "NO";
        // return;

        $finalPrice = $price;
        $percentage = null;
        $totalDiscount = null;

        if ($discount && $discount->is_active) {
            if ($discount->type === 'fixed') {
                $totalDiscount = $discount->value;
                $finalPrice = max(0, $price - $totalDiscount);
                $percentage = $price > 0 ? round(($totalDiscount / $price) * 100, 2) : null;
            } elseif ($discount->type === 'percentage') {
                $percentage = $discount->value;
                $totalDiscount = round(($percentage / 100) * $price, 2);
                $finalPrice = max(0, $price - $totalDiscount);
            }
            $model->old_price = $price;
            $model->discount_id = $discount->id;
        } elseif ($oldPrice && $oldPrice > $price) {
            $totalDiscount = $oldPrice - $price;
            $percentage = $oldPrice > 0 ? round(($totalDiscount / $oldPrice) * 100, 2) : null;
            // setting null to discount_id if discount is null
            $model->discount_id = null;
        } else {
            // setting null to discount_id if discount is null
            $model->discount_id = null;
        }

        // $model->final_price = $finalPrice;
        $model->price = $finalPrice;
        $model->discount_percentage = $percentage;
        $model->total_discount = $totalDiscount;
    }

    public function calculateWeightAndVolume($weight, $length, $width, $height, $defaultUnit): array
    {
        // Преобразуем вес в граммы
        $weightInGrams = match ($defaultUnit?->id) {
            3 => $weight * 1000,     // кг → г
            6 => $weight,            // г
            10 => $weight / 1000,     // мг → г
            default => $weight,      // Assume grams
        };

        // Преобразуем объём в м³ (если всё в см)
        $volumeInM3 = 0;
        if ($length && $width && $height) {
            $volumeInM3 = ($length * $width * $height) / 1_000_000;
        }

        return [
            'weight' => round($weightInGrams, 3),
            'volume' => round($volumeInM3, 6),
        ];
    }


    ////new

    public function applyPromoCodeToCollection(Collection $products, ?PromoCode $promoCode = null): void
    {
        if (!$promoCode || !$promoCode->isAvailable()) {
            return;
        }

        foreach ($products as $product) {
            // Проверяем, применим ли промокод к этому продукту
            if (!$promoCode->isApplicableToProduct($product->id)) {
                continue;
            }

            // Исключаем подарочные сертификаты
            if ($product->name === 'Подарочный сертификат') {
                continue;
            }

            $this->applyPromoCodeToProduct($product, $promoCode);

            // Применяем к вариантам
            if ($product->relationLoaded('variants')) {
                foreach ($product->variants as $variant) {
                    // Проверяем родителя варианта
                    if ($variant->product_id) {
                        $parentProduct = $variant->relationLoaded('product')
                            ? $variant->product
                            : Product::find($variant->product_id);

                        if ($parentProduct && $parentProduct->name === 'Подарочный сертификат') {
                            continue;
                        }
                    }

                    // Исключаем варианты с именем подарочного сертификата
                    if ($variant->name === 'Подарочный сертификат') {
                        continue;
                    }

                    $this->applyPromoCodeToProduct($variant, $promoCode);
                }
            }
        }
    }


    public function applyPromoCodeToProduct($model, PromoCode $promoCode): void
    {
        // Определяем, есть ли у товара скидка
        $hasDiscount = $model->discount_id || ($model->old_price && $model->old_price > $model->price);

        // Проверяем, можно ли применить промокод
        if (!$promoCode->canApplyToProductWithDiscount($hasDiscount)) {
            $model->promo_code_applicable = false;
            return;
        }

        // Получаем оригинальную и текущую цену
        $originalPrice = $model->old_price && $model->old_price > $model->price
            ? $model->old_price
            : $model->price;

        $currentPrice = $model->price;

        // Рассчитываем итоговую цену с промокодом
        $result = $promoCode->calculateFinalPrice($originalPrice, $currentPrice, $hasDiscount);

        // Сохраняем информацию о промокоде
        $model->promo_code_id = $promoCode->id;
        $model->promo_code_discount = $result['promo_discount'];
        $model->price_with_promo = $result['final_price'];
        $model->promo_code_applicable = true;

        // Если промокод заменяет скидку, сохраняем старую цену
        if ($promoCode->discount_behavior === PromoCode::DISCOUNT_BEHAVIOR_REPLACE && $hasDiscount) {
            $model->original_discount_replaced = true;
            $model->price_before_promo = $currentPrice;
        }
    }


    public function applyDiscountsAndPromoCode(Collection $products, ?PromoCode $promoCode = null): void
    {
        // Сначала применяем обычные скидки
        $this->applyDiscountsToCollection($products);

        // Затем применяем промокод
        if ($promoCode) {
            $this->applyPromoCodeToCollection($products, $promoCode);
        }
    }


}
