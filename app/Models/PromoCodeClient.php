<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;

class PromoCodeClient extends Pivot
{
    use HasFactory;

    /**
     * Название таблицы
     */
    protected $table = 'promo_code_client';

    /**
     * Массово заполняемые поля
     */
    protected $fillable = [
        'promo_code_id',
        'client_id',
    ];

    /**
     * Приведение типов
     */
    protected $casts = [
        'promo_code_id' => 'integer',
        'client_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Indicates if the IDs are auto-incrementing
     */
    public $incrementing = true;

    /**
     * Indicates if the model should be timestamped
     */
    public $timestamps = true;

    /**
     * Связь с промокодом
     */
    public function promoCode()
    {
        return $this->belongsTo(PromoCode::class, 'promo_code_id');
    }

    /**
     * Связь с клиентом
     */
    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    /**
     * Scope для получения активных промокодов
     */
    public function scopeActivePromoCodes($query)
    {
        return $query->whereHas('promoCode', function ($q) {
            $q->where('is_active', true)
                ->where(function ($subQ) {
                    $subQ->where('expires_at', '>', now())
                        ->orWhereNull('expires_at');
                });
        });
    }

    /**
     * Scope для фильтрации по промокоду
     */
    public function scopeForPromoCode($query, $promoCodeId)
    {
        return $query->where('promo_code_id', $promoCodeId);
    }

    /**
     * Scope для фильтрации по клиенту
     */
    public function scopeForClient($query, $clientId)
    {
        return $query->where('client_id', $clientId);
    }
}
