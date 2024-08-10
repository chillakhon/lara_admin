<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Color extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $guarded = false;

    public function colorOptionValues()
    {
        return $this->hasMany(ColorOptionValue::class);
    }

    public function colorCategory()
    {
        return $this->belongsTo(ColorCategory::class, 'color_category_id');
    }

    public function images()
    {
        return $this->morphToMany(Image::class, 'imagable', 'imagables');
    }

    public function mainImage()
    {
        return $this->morphToMany(Image::class, 'imagable', 'imagables')
            ->wherePivot('is_main', true);
    }
}
