<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

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
