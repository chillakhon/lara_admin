<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderAddress extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'country',
        'region',
        'city',
        'postal_code',
        'address',
        'entrance',
        'floor',
        'intercom',
        'delivery_comment',
        'delivery_date',
        'buyer_comment',
    ];

    protected $casts = [
        'delivery_date' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
