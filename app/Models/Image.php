<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Image extends Model
{
    use HasFactory;
    public $timestamps = false;

    protected $fillable = [
        'path',
        'url',
        'is_main',
        'order'
    ];
    public function imageable(): MorphTo
    {
        return $this->morphTo();
    }

    public function optionValues()
    {
        return $this->morphedByMany(OptionValue::class, 'imageable')->withTimestamps();
    }

    public function products()
    {
        return $this->morphedByMany(Product::class, 'imageable')
            ->withPivot('product_variant_id')
            ->withTimestamps();
    }

}
