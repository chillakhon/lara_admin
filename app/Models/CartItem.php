<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "cart_items";
    protected $guarded = ['id'];


    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }


    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }


    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }


    public function color(): BelongsTo
    {
        return $this->belongsTo(Color::class);
    }


    public function getTotalPriceAttribute(): float
    {
        return $this->quantity * ($this->price ?? 0);
    }
}
