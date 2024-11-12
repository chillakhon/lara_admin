<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RecipeItem extends Model
{

    use softDeletes;

    protected $fillable = [
        'recipe_id',
        'component_type',
        'component_id',
        'quantity',
        'unit_id',
        'waste_percentage',
        'sort_order',
        'notes'
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'waste_percentage' => 'decimal:2',
        'sort_order' => 'integer'
    ];

    public function recipe()
    {
        return $this->belongsTo(Recipe::class);
    }

    public function component()
    {
        return $this->morphTo('component', 'component_type', 'component_id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }
}
