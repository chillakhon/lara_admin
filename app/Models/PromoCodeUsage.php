<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PromoCodeUsage extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'promo_code_id',
        'client_id',
        'order_id',
        'discount_amount',
    ];

    public function promoCode()
    {
        return $this->belongsTo(PromoCode::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
