<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ColorCategory extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $guarded = false;


    public function colors() {
        return $this->hasMany(Color::class, 'color_category_id');
    }
}
