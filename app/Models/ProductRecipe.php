<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductRecipe extends Model
{
    protected $table = 'product_recipes';

    protected $guarded = ['id'];
    // protected $fillable = [
    //     'recipe_id',
    //     'product_id',
    //     'product_variant_id',
    //     'is_default'
    // ];

    public function product()
    {
        return $this->hasOne(Product::class, 'id', 'product_id');
    }

    public function product_variant()
    {
        return $this->hasOne(ProductVariant::class, 'id', 'product_variant_id');
    }
}