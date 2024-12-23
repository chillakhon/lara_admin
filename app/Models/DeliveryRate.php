<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'delivery_method_id',
        'delivery_zone_id',
        'min_weight',
        'max_weight',
        'min_total',
        'max_total',
        'price',
        'estimated_days'
    ];

    protected $casts = [
        'min_weight' => 'decimal:3',
        'max_weight' => 'decimal:3',
        'min_total' => 'decimal:2',
        'max_total' => 'decimal:2',
        'price' => 'decimal:2'
    ];

    public function deliveryMethod(): BelongsTo
    {
        return $this->belongsTo(DeliveryMethod::class);
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(DeliveryZone::class, 'delivery_zone_id');
    }
} 