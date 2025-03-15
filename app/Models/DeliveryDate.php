<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryDate extends Model
{
    use HasFactory;

    protected $fillable = ['order_id', 'date'];

    // Связь с заказом
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
