<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'abbreviation', 'description'];

    public function inventoryBatches()
    {
        return $this->hasMany(InventoryBatch::class);
    }

    public function inventoryBalances()
    {
        return $this->hasMany(InventoryBalance::class);
    }

    public function inventoryTransactions()
    {
        return $this->hasMany(InventoryTransaction::class);
    }
}
