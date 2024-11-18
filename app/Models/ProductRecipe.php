<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductRecipe extends Model
{
    protected $table = 'product_recipes';
    
    protected $fillable = [
        'recipe_id',
        'product_id',
        'product_variant_id',
        'is_default'
    ];
}