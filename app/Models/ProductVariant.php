<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasRelationships;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductVariant extends Model
{
    use HasFactory, SoftDeletes, HasRelationships;

    // protected $fillable = [
    //     'product_id',
    //     'name',
    //     'sku',
    //     'price',
    //     'additional_cost',
    //     'type',
    //     'unit_id',
    //     'is_active'
    // ];

    protected $guarded = ['id'];

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
        return $this->morphMany(Image::class, 'item');
    }

    public function main_image()
    {
        return $this->morphOne(Image::class, 'item')->where('is_main', true);
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

    public function activeRecipe(): HasOne
    {
        return $this->hasOne(Recipe::class)
            ->where('is_active', true)
            ->where('is_default', true);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function productionBatches(): HasMany
    {
        return $this->hasMany(ProductionBatch::class);
    }

    public function inventoryBalances(): HasMany
    {
        return $this->hasMany(InventoryBalance::class, 'item_id')
            ->where('item_type', 'variant');
    }

    public function inventoryBalance(): MorphOne
    {
        return $this->morphOne(InventoryBalance::class, 'item', 'item_type', 'item_id');
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

    public function morphClass()
    {
        return 'App\\Models\\ProductVariant';
    }


    public function colors(): MorphToMany
    {
        return $this->morphToMany(Color::class, 'colorable');
    }

    public function discountable()
    {
        return $this->morphOne(Discountable::class, 'discountable');
    }

    public function discount()
    {
        return $this->discountable?->discount;
    }

    public function table_color()
    {
        return $this->belongsTo(Color::class, 'color_id');
    }
}
