<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Client;
use App\Models\Discount;
use Illuminate\Support\Facades\Cache;

class DiscountService
{
    private $activeDiscounts;

    public function __construct()
    {
        // Кэшируем активные скидки при создании сервиса
        $this->activeDiscounts = Cache::remember('active_discounts', 60, function () {
            return Discount::where('is_active', true)
                ->where(function ($query) {
                    $query->whereNull('starts_at')
                        ->orWhere('starts_at', '<=', now());
                })
                ->where(function ($query) {
                    $query->whereNull('ends_at')
                        ->orWhere('ends_at', '>=', now());
                })
                ->with(['products:id', 'productVariants:id', 'categories:id'])
                ->get();
        });
    }

    public function calculateDiscounts($product, $variant = null)
    {
        $applicableDiscounts = collect();

        foreach ($this->activeDiscounts as $discount) {
            // Проверяем глобальные скидки
            if ($discount->discount_type === 'all') {
                $applicableDiscounts->push($discount);
                continue;
            }

            // Проверяем скидки по категориям
            if ($discount->discount_type === 'category') {
                $productCategories = $product->categories->pluck('id')->toArray();
                $discountCategories = $discount->categories->pluck('id')->toArray();
                if (array_intersect($productCategories, $discountCategories)) {
                    $applicableDiscounts->push($discount);
                    continue;
                }
            }

            // Проверяем специфичные скидки
            if ($discount->discount_type === 'specific') {
                if ($variant && $discount->productVariants->contains($variant->id)) {
                    $applicableDiscounts->push($discount);
                } elseif ($discount->products->contains($product->id)) {
                    $applicableDiscounts->push($discount);
                }
            }
        }

        return $applicableDiscounts;
    }

    protected function checkConditions($discount, $quantity, $price, $client)
    {
        $conditions = $discount->conditions;

        if (!empty($conditions['min_quantity']) && $quantity < $conditions['min_quantity']) {
            return false;
        }

        if (!empty($conditions['min_price']) && $price < $conditions['min_price']) {
            return false;
        }

        if (!empty($conditions['client_level_id']) && (!$client || $client->level_id != $conditions['client_level_id'])) {
            return false;
        }

        return true;
    }

    // Другие вспомогательные методы...
} 