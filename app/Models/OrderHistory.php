<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderHistory extends Model
{
    protected $fillable = [
        'order_id',
        'user_id',
        'status',
        'payment_status',
        'comment'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Получить текстовое представление статуса
     */
    public function getStatusLabelAttribute(): string
    {
        return Order::getStatuses()[$this->status] ?? $this->status;
    }

    /**
     * Получить текстовое представление статуса оплаты
     */
    public function getPaymentStatusLabelAttribute(): ?string
    {
        return $this->payment_status 
            ? (Order::getPaymentStatuses()[$this->payment_status] ?? $this->payment_status)
            : null;
    }
} 