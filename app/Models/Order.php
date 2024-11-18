<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'client_id',
        'order_number',
        'total_amount',
        'status',
        'notes',
        'promo_code_id',
        'discount_amount'
    ];

    protected $casts = [
        'total_amount' => 'float',
        'discount_amount' => 'float'
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function promoCode()
    {
        return $this->belongsTo(PromoCode::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function updateTotalAmount()
    {
        $this->total_amount = $this->items->sum(function ($item) {
            return $item->quantity * $item->price;
        });
        $this->save();
    }
}
