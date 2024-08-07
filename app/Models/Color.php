<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Color extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $guarded = false;

    public function colorOptionValues()
    {
        return $this->hasMany(ColorOptionValue::class);
    }
}
