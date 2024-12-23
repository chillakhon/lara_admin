<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryMethod extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'description',
        'is_active',
        'settings',
        'provider_class'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array'
    ];

    public function zones(): HasMany
    {
        return $this->hasMany(DeliveryZone::class);
    }

    public function rates(): HasMany
    {
        return $this->hasMany(DeliveryRate::class);
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }

    public function getDeliveryService(): DeliveryService
    {
        $className = $this->provider_class;
        return new $className($this->settings);
    }
} 