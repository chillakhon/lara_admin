<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderPayment extends Model
{
    const STATUS_PENDING = 'pending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_REFUNDED = 'refunded';

    protected $fillable = [
        'order_id',
        'payment_method',
        'payment_provider',
        'payment_id',
        'amount',
        'status',
        'payment_data',
        'processed_at'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_data' => 'json',
        'processed_at' => 'datetime'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
} 