<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Material extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $fillable = ['title', 'unit_id'];

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function inventoryBatches()
    {
        return $this->morphMany(InventoryBatch::class, 'item');
    }

    public function inventoryBalance()
    {
        return $this->morphOne(InventoryBalance::class, 'item');
    }

    public function inventoryTransactions()
    {
        return $this->morphMany(InventoryTransaction::class, 'item');
    }

    public function productComponents()
    {
        return $this->hasMany(ProductComponent::class);
    }
}
