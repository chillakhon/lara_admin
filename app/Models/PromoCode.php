<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class PromoCode extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'code',
        'image',
        'description',
        'discount_amount',
        'discount_type',
        'starts_at',
        'expires_at',
        'max_uses',
        'total_uses',
        'is_active',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
        'discount_amount' => 'decimal:2',
        'max_uses' => 'integer',
        'total_uses' => 'integer',
    ];

//    protected $appends = [
//        'image_url',
//    ];

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

//    public function usages()
//    {
//        return $this->hasMany(Order::class);
//    }


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

        if ($this->max_uses && $this->total_uses >= $this->max_uses) {
            return false;
        }

        return true;
    }

    public function isAvailableForClient($clientId)
    {
        if (!$this->isAvailable()) {
            return false;
        }

        // Если промокод привязан к конкретным клиентам
        if ($this->clients()->exists()) {
            return $this->clients()->where('client_id', $clientId)->exists();
        }

        // Если промокод уже использован этим клиентом
        if ($this->usages()->where('client_id', $clientId)->exists()) {
            return false;
        }

        return true;
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
