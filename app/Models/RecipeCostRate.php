<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
