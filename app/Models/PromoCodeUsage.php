<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PromoCodeUsage extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'promo_code_id',
        'client_id',
        'order_id',
        'discount_amount', // Оставляем как есть
    ];

    protected $casts = [
        'discount_amount' => 'decimal:2',
    ];

    /**
     * Промокод, который был использован
     */
    public function promoCode()
    {
        return $this->belongsTo(PromoCode::class);
    }

    /**
     * Клиент, который использовал промокод
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Заказ, в котором был использован промокод
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Scope для фильтрации по промокоду
     */
    public function scopeByPromoCode($query, $promoCodeId)
    {
        return $query->where('promo_code_id', $promoCodeId);
    }

    /**
     * Scope для фильтрации по клиенту
     */
    public function scopeByClient($query, $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    /**
     * Получить статистику использования промокода
     */
    public static function getPromoCodeStats($promoCodeId)
    {
        return self::where('promo_code_id', $promoCodeId)
            ->selectRaw('
                COUNT(*) as total_usages,
                COUNT(DISTINCT client_id) as unique_clients,
                SUM(discount_amount) as total_discount_given,
                AVG(discount_amount) as avg_discount_per_usage,
                MIN(created_at) as first_used_at,
                MAX(created_at) as last_used_at
            ')
            ->first();
    }
}
