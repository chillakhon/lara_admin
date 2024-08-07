<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ColorOptionValue extends Model
{
    use HasFactory;

    public function color()
    {
        return $this->belongsTo(Color::class);
    }

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function colorOption()
    {
        return $this->belongsTo(ColorOption::class, 'color_options_id');
    }
}
