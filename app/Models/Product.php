<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $fillable = ['name', 'description', 'is_available'];

    public function components()
    {
        return $this->hasMany(ProductComponent::class);
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class);
    }

    public function sizes()
    {
        return $this->hasMany(ProductSize::class);
    }

    public function colorOptions()
    {
        return $this->hasMany(ColorOption::class);
    }

    public function images()
    {
        return $this->morphToMany(Image::class, 'imagable')
            ->withPivot('product_variant_id')
            ->withTimestamps();
    }

    public function getImagesForVariant($variantId)
    {
        return $this->images()
            ->wherePivot('product_variant_id', $variantId)
            ->get();
    }
}
