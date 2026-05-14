<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @OA\Schema(
 *     schema="RecipeCostRate",
 *     type="object",
 *     @OA\Property(property="id", type="integer", format="int64"),
 *     @OA\Property(property="recipe_id", type="integer", format="int64"),
 *     @OA\Property(property="cost_category_id", type="integer", format="int64"),
 *     @OA\Property(property="rate_per_unit", type="number", format="float"),
 *     @OA\Property(property="fixed_rate", type="number", format="float", default=0.00),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class RecipeCostRate extends Model
{
    protected $fillable = [
        'recipe_id',
        'cost_category_id',
        'rate_per_unit',
        'fixed_rate'
    ];

    protected $casts = [
        'rate_per_unit' => 'decimal:2',
        'fixed_rate' => 'decimal:2'
    ];

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(CostCategory::class, 'cost_category_id');
    }

    // Метод для расчета стоимости для заданного количества
    public function calculateCost(float $quantity): float
    {
        return ($this->rate_per_unit * $quantity) + $this->fixed_rate;
    }
}
