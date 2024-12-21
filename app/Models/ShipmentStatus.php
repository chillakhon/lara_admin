<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShipmentStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description'
    ];

    public const NEW = 'new';
    public const PROCESSING = 'processing';
    public const READY_FOR_PICKUP = 'ready_for_pickup';
    public const IN_TRANSIT = 'in_transit';
    public const DELIVERED = 'delivered';
    public const CANCELLED = 'cancelled';
    public const RETURNED = 'returned';

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class, 'status_id');
    }
} 