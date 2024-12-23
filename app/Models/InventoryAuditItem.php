<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryAuditItem extends Model
{
    protected $fillable = [
        'inventory_audit_id',
        'item_type',
        'item_id',
        'unit_id',
        'expected_quantity',
        'actual_quantity',
        'difference',
        'cost_per_unit',
        'difference_cost',
        'notes',
        'counted_by',
        'counted_at'
    ];

    protected $casts = [
        'expected_quantity' => 'decimal:3',
        'actual_quantity' => 'decimal:3',
        'difference' => 'decimal:3',
        'cost_per_unit' => 'decimal:2',
        'difference_cost' => 'decimal:2',
        'counted_at' => 'datetime'
    ];

    public function audit(): BelongsTo
    {
        return $this->belongsTo(InventoryAudit::class, 'inventory_audit_id');
    }

    public function item(): MorphTo
    {
        return $this->morphTo('item');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function countedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'counted_by');
    }

    public function history(): HasMany
    {
        return $this->hasMany(InventoryAuditItemHistory::class);
    }

    public function calculateDifference(): void
    {
        if ($this->actual_quantity !== null) {
            $this->difference = $this->actual_quantity - $this->expected_quantity;
            $this->difference_cost = $this->difference * $this->cost_per_unit;
            $this->save();
        }
    }
} 