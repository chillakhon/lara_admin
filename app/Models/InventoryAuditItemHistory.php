<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryAuditItemHistory extends Model
{
    protected $fillable = [
        'inventory_audit_item_id',
        'user_id',
        'old_quantity',
        'new_quantity',
        'notes'
    ];

    protected $casts = [
        'old_quantity' => 'decimal:3',
        'new_quantity' => 'decimal:3'
    ];

    public function auditItem(): BelongsTo
    {
        return $this->belongsTo(InventoryAuditItem::class, 'inventory_audit_item_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
} 