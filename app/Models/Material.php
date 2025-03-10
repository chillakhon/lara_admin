<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @OA\Schema(
 *     schema="Material",
 *     type="object",
 *     @OA\Property(property="id", type="integer", format="int64"),
 *     @OA\Property(property="title", type="string", maxLength=255),
 *     @OA\Property(property="unit_id", type="integer", format="int64"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class Material extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'unit_id'
    ];

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function inventoryBalance(): MorphOne
    {
        return $this->morphOne(InventoryBalance::class, 'item');
    }

    public function recipeItems(): MorphMany
    {
        return $this->morphMany(RecipeItem::class, 'component');
    }

    public function inventoryBatches()
    {
        return $this->morphMany(InventoryBatch::class, 'item');
    }

    public function inventoryTransactions()
    {
        return $this->morphMany(InventoryTransaction::class, 'item');
    }

    public function getCurrentStock(): float
    {
        return $this->inventoryBalance?->total_quantity ?? 0;
    }

    public function getAverageCost(): float
    {
        return $this->inventoryBalance?->average_price ?? 0;
    }

    public function checkAvailability(float $quantity): bool
    {
        return $this->getCurrentStock() >= $quantity;
    }


}
