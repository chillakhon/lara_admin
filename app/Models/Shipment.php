<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Shipment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_id',
        'delivery_method_id',
        'status_id',
        'tracking_number',
        'provider_data',
        'shipping_date',
        'estimated_delivery_date',
        'actual_delivery_date',
        'shipping_address',
        'recipient_name',
        'recipient_phone',
        'weight',
        'dimensions',
        'cost',
        'notes'
    ];

    protected $casts = [
        'provider_data' => 'array',
        'dimensions' => 'array',
        'shipping_date' => 'datetime',
        'estimated_delivery_date' => 'datetime',
        'actual_delivery_date' => 'datetime',
        'weight' => 'decimal:3',
        'cost' => 'decimal:2'
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function deliveryMethod(): BelongsTo
    {
        return $this->belongsTo(DeliveryMethod::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(ShipmentStatus::class, 'status_id');
    }
}
