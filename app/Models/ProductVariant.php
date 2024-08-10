<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $fillable = ['product_id', 'name', 'article', 'additional_cost', 'price', 'stock'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function optionValues()
    {
        return $this->belongsToMany(OptionValue::class);
    }

    public function components()
    {
        return $this->hasMany(ProductComponent::class);
    }
}
