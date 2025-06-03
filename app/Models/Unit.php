<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *     schema="Unit",
 *     type="object",
 *     @OA\Property(property="id", type="integer", format="int64"),
 *     @OA\Property(property="name", type="string", maxLength=255),
 *     @OA\Property(property="abbreviation", type="string", maxLength=255),
 *     @OA\Property(property="description", type="string", description="Описание", nullable=true)
 * )
 */
class Unit extends Model
{
    use HasFactory;


    public $timestamps = false;

    protected $fillable = ['name', 'abbreviation', 'description', 'meta_data'];

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
