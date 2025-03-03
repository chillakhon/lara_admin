<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class OrderDiscount extends Model
{
    protected $fillable = [
        'order_id',
        'discount_id',
        'discountable_type',
        'discountable_id',
        'original_price',
        'discount_amount',
        'final_price'
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function discount(): BelongsTo
    {
        return $this->belongsTo(Discount::class);
    }

    public function discountable(): MorphTo
    {
        return $this->morphTo();
    }
} 