<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PromoCode extends Model
{
    use HasFactory;
    use SoftDeletes;

    // Константы для типов применения скидок
    const DISCOUNT_BEHAVIOR_REPLACE = 'replace'; // Заменяет скидку продукта
    const DISCOUNT_BEHAVIOR_STACK = 'stack';     // Добавляется поверх скидки
    const DISCOUNT_BEHAVIOR_SKIP = 'skip';       // Пропускает товары со скидкой

    protected $fillable = [
        'code',
        'image',
        'description',
        'discount_amount',
        'discount_type',
        'discount_behavior', // НОВОЕ ПОЛЕ
        'starts_at',
        'expires_at',
        'max_uses',
        'times_uses',
        'is_active',
        'applies_to_all_products',
        'applies_to_all_clients',
        'type',
        'template_type'
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
        'discount_amount' => 'decimal:2',
        'max_uses' => 'integer',
        'times_uses' => 'integer',
        'applies_to_all_products' => 'boolean',
        'applies_to_all_clients' => 'boolean',
    ];

    public function isValid()
    {
        $now = now();
        return $this->is_active &&
            $now->gte($this->starts_at) &&
            $now->lte($this->expires_at) &&
            ($this->max_uses === null || $this->times_used < $this->max_uses);
    }

    /**
     * Связь с использованиями промокода
     */
    public function usages()
    {
        return $this->hasMany(PromoCodeUsage::class);
    }

    public function clients()
    {
        return $this->belongsToMany(Client::class, 'promo_code_client');
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'promo_code_product');
    }

    public function getImageUrlAttribute()
    {
        if ($this->image) {
            return Storage::disk('public')->url($this->image);
        }
        return null;
    }

    public function isAvailable()
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        if ($this->starts_at && $this->starts_at->isFuture()) {
            return false;
        }

        if ($this->max_uses && $this->times_uses >= $this->max_uses) {
            return false;
        }

        return true;
    }

    public function isAvailableForClient($clientId)
    {
        try {
            if (!$this->isAvailable()) {
                return false;
            }

            // Если промокод привязан к конкретным клиентам
            if ($this->clients()->exists()) {
                $exists = $this->clients()->where('client_id', $clientId)->exists();
                return $exists;
            }

            // Если промокод уже использован этим клиентом
            if ($this->usages()->where('client_id', $clientId)->exists()) {
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function isApplicableToProduct($productId): bool
    {
        if ($this->applies_to_all_products) {
            return true;
        }

        return $this->products()->where('product_id', $productId)->exists();
    }

    /**
     * Проверить, можно ли применить промокод к товару с учетом существующих скидок
     */
    public function canApplyToProductWithDiscount($hasDiscount): bool
    {
        // Если у товара нет скидки, промокод всегда применим
        if (!$hasDiscount) {
            return true;
        }

        // Если поведение = skip, не применяем к товарам со скидкой
        if ($this->discount_behavior === self::DISCOUNT_BEHAVIOR_SKIP) {
            return false;
        }

        // Для replace и stack - применяем
        return true;
    }

    /**
     * Рассчитать итоговую цену с учетом промокода и существующей скидки
     *
     * @param float $originalPrice - оригинальная цена товара
     * @param float $currentPrice - текущая цена (возможно со скидкой)
     * @param bool $hasDiscount - есть ли у товара скидка
     * @return array ['final_price' => float, 'promo_discount' => float]
     */
    public function calculateFinalPrice($originalPrice, $currentPrice, $hasDiscount = false): array
    {
        // Если у товара нет скидки, применяем промокод к оригинальной цене
        if (!$hasDiscount) {
            $promoDiscount = $this->calculateDiscount($originalPrice);
            return [
                'final_price' => max(0, $originalPrice - $promoDiscount),
                'promo_discount' => $promoDiscount,
            ];
        }

        // Если есть скидка, поведение зависит от discount_behavior
        switch ($this->discount_behavior) {
            case self::DISCOUNT_BEHAVIOR_REPLACE:
                // Заменяем скидку продукта, применяем промокод к оригинальной цене
                $promoDiscount = $this->calculateDiscount($originalPrice);
                return [
                    'final_price' => max(0, $originalPrice - $promoDiscount),
                    'promo_discount' => $promoDiscount,
                ];

            case self::DISCOUNT_BEHAVIOR_STACK:
                // Применяем промокод поверх текущей цены со скидкой
                $promoDiscount = $this->calculateDiscount($currentPrice);
                return [
                    'final_price' => max(0, $currentPrice - $promoDiscount),
                    'promo_discount' => $promoDiscount,
                ];

            case self::DISCOUNT_BEHAVIOR_SKIP:
                // Не применяем промокод, возвращаем текущую цену
                return [
                    'final_price' => $currentPrice,
                    'promo_discount' => 0,
                ];

            default:
                // По умолчанию применяем к текущей цене
                $promoDiscount = $this->calculateDiscount($currentPrice);
                return [
                    'final_price' => max(0, $currentPrice - $promoDiscount),
                    'promo_discount' => $promoDiscount,
                ];
        }
    }

    /**
     * Рассчитать размер скидки
     */
    public function calculateDiscount($amount)
    {
        if ($this->discount_type === 'percentage') {
            return $amount * ($this->discount_amount / 100);
        }

        return min($this->discount_amount, $amount);
    }
}
