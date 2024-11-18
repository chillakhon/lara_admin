<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComponentConsumption extends Model
{
    protected $table = 'component_consumptions';
    
    protected $fillable = [
        'production_batch_id',
        'component_type',
        'component_id',
        'inventory_batch_id',
        'quantity',
        'price_per_unit',
        'unit_id',
        'waste_quantity',
        'notes'
    ];

    public function productionBatch()
    {
        return $this->belongsTo(ProductionBatch::class);
    }

    public function component()
    {
        return $this->morphTo();
    }

    public function inventoryBatch()
    {
        return $this->belongsTo(InventoryBatch::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }
}
