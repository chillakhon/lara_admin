<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ColorOption extends Model
{
    use HasFactory;

    protected $guarded = false;

    public $timestamps = false;
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function colorOptionValues()
    {
        return $this->hasMany(ColorOptionValue::class, );
    }
}
