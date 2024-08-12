<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ColorOptionValue extends Model
{
    use HasFactory;
    protected $guarded = false;
    public $timestamps = false;

    public function color()
    {
        return $this->belongsTo(Color::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function colorOption()
    {
        return $this->belongsTo(ColorOption::class, 'color_options_id');
    }
}
