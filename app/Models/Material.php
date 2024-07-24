<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Material extends Model
{
    use HasFactory;
    public $timestamps = false;

    protected $fillable = ['name', 'price', 'unit', 'is_calculated', 'formula', 'conversion_factor'];

    protected $casts = [
        'is_calculated' => 'boolean',
        'conversion_factor' => 'float',
    ];


}
