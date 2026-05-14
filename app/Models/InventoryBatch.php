<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class InventoryBatch extends Model
{
    use HasFactory;
    protected $fillable = ['item_type', 'item_id', 'quantity', 'price_per_unit', 'unit_id', 'received_date'];

    public function item(): MorphTo
    {
        return $this->morphTo();
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function transactions()
    {
        return $this->hasMany(InventoryTransaction::class, 'batch_id');
    }
}
