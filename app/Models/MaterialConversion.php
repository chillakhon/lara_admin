<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaterialConversion extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $fillable = ['material_id', 'from_unit', 'to_unit', 'conversion_factor'];

    public function material()
    {
        return $this->belongsTo(Material::class);
    }
}
