<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Discount extends Model
{
    use SoftDeletes;

    // protected $fillable = [
    //     'name',
    //     'type',
    //     'value',
    //     'is_active',
    //     'starts_at',
    //     'ends_at',
    //     'priority',
    //     'conditions',
    //     'discount_type'
    // ];

    protected $guarded = ['id'];

    protected $casts = [
        'conditions' => 'json',
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'value' => 'decimal:2'
    ];

    public function products()
    {
        return $this->morphedByMany(Product::class, 'discountable');
    }

    public function productVariants()
    {
        return $this->morphedByMany(ProductVariant::class, 'discountable');
    }

    public function isValid(): bool
    {
        return $this->is_active
            && (!$this->starts_at || $this->starts_at->isPast())
            && (!$this->ends_at || $this->ends_at->isFuture());
    }

    public function categories()
    {
        return $this->morphedByMany(Category::class, 'discountable');
    }
}