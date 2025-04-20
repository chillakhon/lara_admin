<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @OA\Schema(
 *     schema="Recipe",
 *     type="object",
 *     @OA\Property(property="id", type="integer", format="int64"),
 *     @OA\Property(property="name", type="string", maxLength=255),
 *     @OA\Property(property="description", type="string", nullable=true),
 *     @OA\Property(property="output_quantity", type="number", format="float"),
 *     @OA\Property(property="output_unit_id", type="integer", format="int64"),
 *     @OA\Property(property="is_active", type="boolean"),
 *     @OA\Property(property="instructions", type="string", nullable=true),
 *     @OA\Property(property="production_time", type="integer", nullable=true),
 *     @OA\Property(property="created_by", type="integer", format="int64"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 *     @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true)
 * )
 */
class Recipe extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'output_quantity',
        'output_unit_id',
        'is_active',
        'created_by',
        'instructions',
        'production_time'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'output_quantity' => 'decimal:3',
        'production_time' => 'integer'
    ];

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_recipes')
            ->withPivot('is_default')
            ->withTimestamps();
    }

    public function selectedVariants()
    {
        return $this->belongsToMany(ProductVariant::class, 'product_recipes')
            ->wherePivot('product_variant_id', '!=', null)
            ->withPivot('is_default')
            ->withTimestamps();
    }

    public function productVariant(): HasOneThrough
    {
        return $this->hasOneThrough(
            ProductVariant::class,
            ProductRecipe::class,
            'recipe_id', // Внешний ключ в product_recipes
            'id', // Локальный ключ в product_variants
            'id', // Локальный ключ в recipes
            'product_variant_id' // Внешний ключ в product_recipes
        );
    }

    public function product_recipes()
    {
        return $this->hasMany(ProductRecipe::class);
    }

    public function items()
    {
        return $this->hasMany(RecipeItem::class);
    }

    public function outputUnit()
    {
        return $this->belongsTo(Unit::class, 'output_unit_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function costRates()
    {
        return $this->hasMany(RecipeCostRate::class);
    }

    public function costs()
    {
        return $this->hasMany(ProductionBatchCost::class);
    }
}
