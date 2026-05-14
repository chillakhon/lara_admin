<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @OA\Schema(
 *     schema="RecipeItem",
 *     type="object",
 *     @OA\Property(property="id", type="integer", format="int64"),
 *     @OA\Property(property="recipe_id", type="integer", format="int64"),
 *     @OA\Property(property="component_type", type="string", maxLength=255),
 *     @OA\Property(property="component_id", type="integer", format="int64"),
 *     @OA\Property(property="quantity", type="number", format="float"),
 *     @OA\Property(property="unit_id", type="integer", format="int64"),
 *     @OA\Property(property="waste_percentage", type="number", format="float", default=0.00),
 *     @OA\Property(property="sort_order", type="integer", default=0),
 *     @OA\Property(property="notes", type="string", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 *     @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true)
 * )
 */
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
