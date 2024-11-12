<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductVariant extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'product_id',
        'name',
        'sku',
        'price',
        'additional_cost',
        'type',
        'unit_id',
        'is_active'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'additional_cost' => 'decimal:2',
        'is_active' => 'boolean'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function optionValues(): BelongsToMany
    {
        return $this->belongsToMany(
            OptionValue::class,
            'product_variant_option_values'
        )
            ->using(ProductVariantOptionValue::class)
            ->withTimestamps();
    }

    public function getOptionValue(Option $option)
    {
        return $this->optionValues()
            ->whereHas('option', fn($query) => $query->where('id', $option->id))
            ->first();
    }

    public function images()
    {
        return $this->morphToMany(Image::class, 'imageable')
            ->withPivot(['id'])
            ->orderBy('order')
            ->orderBy('is_main', 'desc'); // is_main теперь берется из таблицы images
    }

    public function getMainImageAttribute()
    {
        return $this->images->where('is_main', true)->first()
            ?? $this->images->first();
    }

    public function recipes()
    {
        return $this->belongsToMany(Recipe::class, 'product_recipes')
            ->withPivot('is_default')
            ->withTimestamps();
    }

    public function defaultRecipe()
    {
        return $this->recipes()
            ->wherePivot('is_default', true)
            ->wherePivot('product_variant_id', $this->id)
            ->first();
    }

    public function activeRecipe()
    {
        return $this->hasOne(Recipe::class)
            ->where('is_active', true)
            ->where('is_default', true);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function productionBatches()
    {
        return $this->hasMany(ProductionBatch::class);
    }

    public function inventoryBalance()
    {
        return $this->morphOne(InventoryBalance::class, 'item');
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
