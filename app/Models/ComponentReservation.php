<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComponentReservation extends Model
{
    protected $fillable = [
        'production_batch_id',
        'component_type',
        'component_id',
        'quantity',
        'unit_id',
        'expires_at'
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'expires_at' => 'datetime'
    ];

    public function productionBatch()
    {
        return $this->belongsTo(ProductionBatch::class);
    }

    public function component()
    {
        return $this->morphTo();
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}
